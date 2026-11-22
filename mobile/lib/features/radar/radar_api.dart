import '../auth/auth_api.dart';

typedef Json = Map<String, dynamic>;

enum RadarPeriod {
  upcoming('Mendatang'),
  all('Semua'),
  history('Riwayat');

  const RadarPeriod(this.label);
  final String label;
}

class PublicEvent {
  PublicEvent.fromJson(Json json)
    : id = json['id'] as int,
      title = json['title'] as String,
      description = json['description'] as String?,
      organizationId = (json['organization'] as Json)['id'] as int,
      organizer = (json['organization'] as Json)['name'] as String,
      faculty = (json['organization'] as Json)['faculty'] as String?,
      program = (json['organization'] as Json)['study_program'] as String?,
      startsAt = DateTime.parse(json['starts_at'] as String).toUtc(),
      endsAt = DateTime.parse(json['ends_at'] as String).toUtc(),
      venue = json['venue'] as String?,
      status = json['status'] as String,
      poster = json['poster_url'] as String?;

  final int id;
  final String title;
  final String? description;
  final int organizationId;
  final String organizer;
  final String? faculty;
  final String? program;
  final DateTime startsAt;
  final DateTime endsAt;
  final String? venue;
  final String status;
  final String? poster;

  bool isHistorical(DateTime reference) =>
      status != 'published' || !endsAt.isAfter(reference);

  String state(DateTime reference) => switch (status) {
    'cancelled' => 'Dibatalkan',
    'completed' => 'Selesai',
    _ when isHistorical(reference) => 'Jadwal berlalu',
    _ when !startsAt.isAfter(reference) => 'Sedang berlangsung',
    _ => 'Mendatang',
  };
}

class RadarOption {
  RadarOption.fromJson(Json json)
    : id = json['id'] as int,
      name = json['name'] as String,
      facultyId = json['faculty_id'] as int?;

  final int id;
  final String name;
  final int? facultyId;
}

class RadarPage {
  RadarPage.fromJson(Json json)
    : events = (json['data'] as List<dynamic>)
          .map((item) => PublicEvent.fromJson(item as Json))
          .toList(),
      page = (json['meta'] as Json)['current_page'] as int,
      lastPage = (json['meta'] as Json)['last_page'] as int,
      total = (json['meta'] as Json)['total'] as int,
      reference = DateTime.parse(
        (json['radar'] as Json)['reference_time'] as String,
      ),
      featured = _featured(json['radar'] as Json),
      faculties = _options(json['radar'] as Json, 'faculties'),
      programs = _options(json['radar'] as Json, 'study_programs'),
      organizations = _options(json['radar'] as Json, 'organizations');

  final List<PublicEvent> events;
  final int page;
  final int lastPage;
  final int total;
  final DateTime reference;
  final PublicEvent? featured;
  final List<RadarOption> faculties;
  final List<RadarOption> programs;
  final List<RadarOption> organizations;

  static List<RadarOption> _options(Json meta, String key) =>
      (meta[key] as List<dynamic>)
          .map((item) => RadarOption.fromJson(item as Json))
          .toList();

  static PublicEvent? _featured(Json meta) {
    final value = meta['featured'] as Json?;
    return value == null ? null : PublicEvent.fromJson(value);
  }
}

class RadarApi {
  const RadarApi(this._client);
  final LaravelAuthApi _client;

  Future<RadarPage> list(Map<String, String> query) async {
    final payload = await _client.getPublic('/api/v1/events', query: query);
    return _decode(() => RadarPage.fromJson(payload));
  }

  Future<PublicEvent> detail(int id) async {
    final payload = await _client.getPublic('/api/v1/events/$id');
    return _decode(() => PublicEvent.fromJson(payload['data'] as Json));
  }

  Uri? poster(PublicEvent event) =>
      _client.publicPoster(event.poster, event.id);

  T _decode<T>(T Function() parse) {
    try {
      return parse();
    } on TypeError {
      throw const ApiFailure('Data kegiatan dari server tidak dapat dibaca.');
    } on FormatException {
      throw const ApiFailure('Jadwal kegiatan dari server tidak dapat dibaca.');
    }
  }
}
