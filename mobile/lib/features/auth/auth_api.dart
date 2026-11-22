import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:flutter/foundation.dart' show kDebugMode;

import '../../core/app_config.dart';

class EvaloopProfile {
  const EvaloopProfile({
    required this.id,
    required this.name,
    required this.email,
    this.studyProgram,
  });

  factory EvaloopProfile.fromJson(Map<String, dynamic> data) {
    final program = data['study_program'];
    return EvaloopProfile(
      id: data['id'] as int,
      name: data['name'] as String,
      email: data['email'] as String,
      studyProgram: program is Map<String, dynamic>
          ? program['name'] as String?
          : null,
    );
  }

  final int id;
  final String name;
  final String email;
  final String? studyProgram;
}

class ApiFailure implements Exception {
  const ApiFailure(this.message, {this.status});

  final String message;
  final int? status;
}

abstract interface class AuthApi {
  Uri? get registrationUri;

  Future<void> bootstrap();
  Future<void> login(String email, String password);
  Future<EvaloopProfile> me();
  Future<void> logout();
  void clearSession();
  void close();
}

class LaravelAuthApi implements AuthApi {
  LaravelAuthApi({String baseUrl = AppConfig.apiBaseUrl, HttpClient? client})
    : _base = _parseBase(baseUrl),
      _client = (client ?? HttpClient())..connectionTimeout = _timeout;

  static const _timeout = Duration(seconds: 15);
  static const _localHosts = {'10.0.2.2', '127.0.0.1', 'localhost', '::1'};
  final Uri? _base;
  final HttpClient _client;
  final Map<String, _SessionCookie> _cookies = {};
  String? _csrf;
  int _sessionVersion = 0;

  static Uri? _parseBase(String value) {
    final uri = Uri.tryParse(value.trim());
    if (uri == null ||
        !uri.hasAuthority ||
        uri.host.isEmpty ||
        uri.userInfo.isNotEmpty ||
        uri.hasQuery ||
        uri.hasFragment ||
        (uri.path.isNotEmpty && uri.path != '/')) {
      return null;
    }

    if (uri.scheme == 'https' ||
        (kDebugMode &&
            uri.scheme == 'http' &&
            _localHosts.contains(uri.host))) {
      return uri;
    }

    return null;
  }

  @override
  Uri? get registrationUri => _base?.resolve('/register');

  Future<Map<String, dynamic>> getPublic(
    String path, {
    Map<String, String>? query,
  }) => _request('GET', path, query: query, public: true);

  Future<Map<String, dynamic>> getAuthenticated(
    String path, {
    Map<String, String>? query,
  }) => _request('GET', path, query: query, public: false);

  Future<Map<String, dynamic>> postAuthenticated(
    String path, [
    Map<String, String>? body,
  ]) async {
    if (_csrf == null) await bootstrap();
    return _request(
      'POST',
      path,
      body: body,
      csrf: _csrf,
      public: false,
    ).onError<ApiFailure>((error, stack) async {
      if (error.status != 419) throw error;
      await bootstrap();
      return _request('POST', path, body: body, csrf: _csrf, public: false);
    });
  }

  Future<Map<String, dynamic>> patchAuthenticated(
    String path,
    Map<String, String> body,
  ) async {
    if (_csrf == null) await bootstrap();
    try {
      return await _request(
        'PATCH',
        path,
        body: body,
        csrf: _csrf,
        public: false,
      );
    } on ApiFailure catch (error) {
      if (error.status != 419) rethrow;
      _csrf = null;
      throw const ApiFailure(
        'Token sesi kedaluwarsa. Tekan Simpan status lagi untuk memperbaruinya.',
        status: 419,
      );
    }
  }

  Uri? publicPoster(String? url, int eventId) {
    if (url == null) return null;
    final expectedPath = '/events/$eventId/poster';
    return Uri.tryParse(url)?.path == expectedPath
        ? _base?.resolve(expectedPath)
        : null;
  }

  @override
  Future<void> bootstrap() async {
    _storeToken(await _send('GET', '/api/v1/session'));
  }

  @override
  Future<void> login(String email, String password) async {
    final data = await _mutate('/api/v1/login', {
      'email': email,
      'password': password,
    });
    _storeToken(data);
  }

  @override
  Future<EvaloopProfile> me() async {
    final data = await _send('GET', '/api/v1/me');
    try {
      return EvaloopProfile.fromJson(data);
    } on TypeError {
      throw const ApiFailure('Profil dari server tidak dapat dibaca.');
    }
  }

  @override
  Future<void> logout() async {
    _storeToken(await _mutate('/api/v1/logout'));
  }

  @override
  void clearSession() {
    _sessionVersion++;
    _cookies.clear();
    _csrf = null;
  }

  @override
  void close() {
    clearSession();
    _client.close(force: true);
  }

  void _storeToken(Map<String, dynamic> data) {
    final token = data['csrf_token'];
    if (token is! String || token.isEmpty) {
      _csrf = null;
      throw const ApiFailure('Token sesi dari server tidak tersedia.');
    }

    _csrf = token;
  }

  Future<Map<String, dynamic>> _mutate(
    String path, [
    Map<String, String>? body,
  ]) async {
    if (_csrf == null) await bootstrap();
    try {
      return await _send('POST', path, body: body, csrf: _csrf);
    } on ApiFailure catch (error) {
      if (error.status != 419) rethrow;
      // Laravel rejected the first attempt before executing the mutation.
      await bootstrap();
      return _send('POST', path, body: body, csrf: _csrf);
    }
  }

  Future<Map<String, dynamic>> _send(
    String method,
    String path, {
    Map<String, String>? body,
    String? csrf,
  }) async {
    final payload = await _request(
      method,
      path,
      body: body,
      csrf: csrf,
      public: false,
    );
    final data = payload['data'];
    if (data is! Map<String, dynamic>) {
      throw const ApiFailure('Respons server tidak dapat dibaca.');
    }
    return data;
  }

  Future<Uint8List> getPrivateImage(
    String path, {
    required int maxBytes,
    int? expectedSize,
  }) async {
    return _exchange(
      'GET',
      path,
      'application/json',
      authenticated: true,
      read: (response, _) async {
        final status = response.statusCode;
        if (status != 200) {
          await response.drain<void>();
          throw ApiFailure(_failureMessage(status, const {}), status: status);
        }
        final mime = response.headers.contentType?.mimeType;
        if (!const {'image/jpeg', 'image/png', 'image/webp'}.contains(mime)) {
          await response.drain<void>();
          throw const ApiFailure('Format gambar tidak didukung.');
        }
        final output = BytesBuilder(copy: false);
        await for (final chunk in response) {
          if (output.length + chunk.length > maxBytes) {
            throw const ApiFailure('Ukuran gambar tidak sesuai.');
          }
          output.add(chunk);
        }
        if (output.length == 0 ||
            (expectedSize != null && output.length != expectedSize)) {
          throw const ApiFailure('Gambar tidak dapat dibaca.');
        }
        return output.takeBytes();
      },
    );
  }

  Future<Map<String, dynamic>> _request(
    String method,
    String path, {
    Map<String, String>? body,
    String? csrf,
    Map<String, String>? query,
    required bool public,
  }) => _exchange(
    method,
    path,
    'application/json',
    body: body,
    csrf: csrf,
    query: query,
    authenticated: !public,
    read: (response, updateCookies) async {
      final source = await utf8.decoder.bind(response).join();
      final status = response.statusCode;
      if (status >= 300 && status < 400) {
        throw const ApiFailure('Server mengalihkan permintaan API.');
      }
      if (response.headers.contentType?.mimeType != 'application/json') {
        throw ApiFailure('Server tidak mengirim respons JSON.', status: status);
      }
      final payload = jsonDecode(source);
      if (payload is! Map<String, dynamic>) {
        throw const ApiFailure('Respons server tidak dapat dibaca.');
      }
      updateCookies();
      if (status < 200 || status >= 300) {
        throw ApiFailure(
          public && status == 404
              ? 'Kegiatan tidak ditemukan.'
              : _failureMessage(status, payload),
          status: status,
        );
      }
      return payload;
    },
  );

  Future<T> _exchange<T>(
    String method,
    String path,
    String accept, {
    Map<String, String>? body,
    String? csrf,
    Map<String, String>? query,
    required bool authenticated,
    required Future<T> Function(HttpClientResponse, void Function()) read,
  }) async {
    final base = _base;
    if (base == null) {
      throw const ApiFailure('Alamat API belum dikonfigurasi atau tidak aman.');
    }
    final uri = base.resolve(path).replace(queryParameters: query);
    if (uri.origin != base.origin) {
      throw const ApiFailure('Alamat permintaan tidak valid.');
    }

    final version = _sessionVersion;
    HttpClientRequest? activeRequest;
    var timedOut = false;
    try {
      return await (() async {
        final request = await _client.openUrl(method, uri);
        if (timedOut || (authenticated && version != _sessionVersion)) {
          request.abort();
          throw const ApiFailure('Permintaan tidak lagi aktif.');
        }
        activeRequest = request;
        request.followRedirects = false;
        request.headers.set(HttpHeaders.acceptHeader, accept);
        if (csrf != null) request.headers.set('X-CSRF-TOKEN', csrf);
        if (authenticated) {
          request.cookies.addAll(
            _cookies.values
                .where((item) => item.appliesTo(uri))
                .map((item) => item.cookie),
          );
        }
        if (body != null) {
          request.headers.contentType = ContentType.json;
          request.write(jsonEncode(body));
        }
        final response = await request.close();
        final result = await read(response, () {
          if (authenticated && !timedOut && version == _sessionVersion) {
            _readCookies(uri, response.cookies);
          }
        });
        if (authenticated && version != _sessionVersion) {
          throw const ApiFailure('Permintaan tidak lagi aktif.');
        }
        return result;
      })().timeout(_timeout);
    } on TimeoutException {
      timedOut = true;
      activeRequest?.abort();
      throw const ApiFailure('Koneksi terlalu lama. Coba lagi.');
    } on FormatException {
      throw const ApiFailure('Respons server tidak dapat dibaca.');
    } on HandshakeException {
      throw const ApiFailure('Koneksi HTTPS ke server gagal diverifikasi.');
    } on IOException {
      throw const ApiFailure(
        'Server tidak dapat dijangkau. Periksa koneksi Anda.',
      );
    }
  }

  void _readCookies(Uri uri, List<Cookie> cookies) {
    final now = DateTime.now();
    for (final cookie in cookies) {
      // Laravel's XSRF-TOKEN cookie is unnecessary because the API supplies
      // the token separately. Keep opaque HttpOnly cookies in memory only.
      if (!cookie.httpOnly) continue;
      final domain = cookie.domain
          ?.replaceFirst(RegExp(r'^\.'), '')
          .toLowerCase();
      if (domain != null && domain != uri.host) continue;
      final key = '${cookie.name}:${cookie.path ?? '/'}';
      if (cookie.value.isEmpty ||
          (cookie.maxAge != null && cookie.maxAge! <= 0)) {
        _cookies.remove(key);
        continue;
      }
      final expires = cookie.maxAge == null
          ? cookie.expires
          : now.add(Duration(seconds: cookie.maxAge!));
      _cookies[key] = _SessionCookie(cookie, expires);
    }
  }

  String _failureMessage(int status, Map<String, dynamic> payload) {
    final errors = payload['errors'];
    if (status == 422 && errors is Map) {
      final messages = errors.values.whereType<List>().expand((items) => items);
      final first = messages.whereType<String>().firstOrNull;
      if (first != null) return first;
    }
    return switch (status) {
      401 => 'Sesi berakhir. Silakan masuk kembali.',
      403 => 'Anda tidak memiliki akses.',
      404 => 'Data tidak ditemukan atau sudah tidak dapat diakses.',
      409 => 'Sesi sudah aktif. Muat ulang sesi untuk melanjutkan.',
      419 => 'Sesi tidak dapat diperbarui. Coba lagi.',
      422 => 'Periksa kembali isian Anda.',
      429 => 'Terlalu banyak percobaan. Coba lagi nanti.',
      _ => 'Server tidak dapat memproses permintaan.',
    };
  }
}

class _SessionCookie {
  const _SessionCookie(this.cookie, this.expires);

  final Cookie cookie;
  final DateTime? expires;

  bool appliesTo(Uri uri) {
    if ((cookie.secure && uri.scheme != 'https') ||
        (expires != null && !expires!.isAfter(DateTime.now()))) {
      return false;
    }
    final path = cookie.path ?? '/';
    return uri.path == path ||
        uri.path.startsWith(path.endsWith('/') ? path : '$path/');
  }
}
