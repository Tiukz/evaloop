import 'package:flutter/foundation.dart';

import 'auth_api.dart';

enum SessionPhase { checking, signedOut, signedIn, unavailable }

class AuthSession extends ChangeNotifier {
  AuthSession(this._api);

  final AuthApi _api;
  SessionPhase phase = SessionPhase.checking;
  EvaloopProfile? profile;
  String? message;
  bool busy = false;
  bool signedInFromLogin = false;
  bool _disposed = false;

  Uri? get registrationUri => _api.registrationUri;

  Future<void> check() async {
    if (busy || _disposed) return;
    final hadSession = phase == SessionPhase.signedIn;
    phase = SessionPhase.checking;
    await _run(() async {
      try {
        await _api.bootstrap();
        await _loadProfile();
      } on ApiFailure catch (error) {
        if (error.status == 401) {
          _reset();
          if (hadSession) message = error.message;
        } else {
          profile = null;
          phase = SessionPhase.unavailable;
          message = error.message;
        }
      }
    });
  }

  Future<void> signIn(String email, String password) async {
    await _run(() async {
      var authenticated = false;
      try {
        await _api.login(email, password);
        authenticated = true;
        await _loadProfile();
        signedInFromLogin = true;
      } on ApiFailure catch (error) {
        if (authenticated || error.status == 409) {
          phase = SessionPhase.unavailable;
        }
        message = error.message;
      }
    });
  }

  Future<void> signOut() async {
    await _run(() async {
      try {
        await _api.logout();
        _reset();
      } on ApiFailure catch (error) {
        if (error.status == 401) _reset();
        message = error.message;
      }
    });
  }

  void expire() {
    if (_disposed || phase != SessionPhase.signedIn) return;
    _reset();
    message = 'Sesi berakhir. Silakan masuk kembali.';
    notifyListeners();
  }

  Future<void> _loadProfile() async {
    profile = await _api.me();
    phase = SessionPhase.signedIn;
  }

  void _reset() {
    _api.clearSession();
    profile = null;
    signedInFromLogin = false;
    phase = SessionPhase.signedOut;
  }

  Future<void> _run(Future<void> Function() action) async {
    if (busy || _disposed) return;
    busy = true;
    message = null;
    notifyListeners();
    try {
      await action();
    } finally {
      busy = false;
      if (!_disposed) notifyListeners();
    }
  }

  @override
  void dispose() {
    _disposed = true;
    _api.close();
    super.dispose();
  }
}
