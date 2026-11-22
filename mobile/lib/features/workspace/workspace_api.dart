import 'dart:typed_data';

import '../auth/auth_api.dart';
import '../meetings/meeting_models.dart';
import 'collision_report.dart';

typedef _Json = Map<String, dynamic>;

class WorkspaceEvent {
  WorkspaceEvent._fromJson(_Json json)
    : id = json['id'] as int,
      title = json['title'] as String,
      organization = json['organization'] as String,
      startsAt = DateTime.parse(json['starts_at'] as String),
      status = json['status'] as String;

  final int id;
  final String title;
  final String organization;
  final DateTime startsAt;
  final String status;
}

class WorkspaceDirectory {
  WorkspaceDirectory._fromJson(_Json json)
    : items = (json['data'] as List<dynamic>)
          .map((item) => WorkspaceEvent._fromJson(item as _Json))
          .toList(),
      featured = json['featured'] == null
          ? null
          : WorkspaceEvent._fromJson(json['featured'] as _Json),
      page = (json['meta'] as _Json)['current_page'] as int,
      lastPage = (json['meta'] as _Json)['last_page'] as int,
      total = (json['meta'] as _Json)['total'] as int;

  final List<WorkspaceEvent> items;
  final WorkspaceEvent? featured;
  final int page;
  final int lastPage;
  final int total;
}

class WorkspaceSummary {
  WorkspaceSummary._fromJson(_Json json)
    : event = WorkspaceEvent._fromJson(json),
      endsAt = DateTime.parse(json['ends_at'] as String),
      series = json['series'] as String?,
      venue = json['venue'] as String?,
      hasPoster = json['has_poster'] == true,
      canManage = json['can_manage'] == true,
      members = (json['committee'] as _Json)['total'] as int,
      coordinator = (json['committee'] as _Json)['coordinator'] as String?,
      myRole = (json['committee'] as _Json)['role'] as String,
      todo = (json['task_counts'] as _Json)['todo'] as int,
      inProgress = (json['task_counts'] as _Json)['in_progress'] as int,
      done = (json['task_counts'] as _Json)['done'] as int;

  final WorkspaceEvent event;
  final DateTime endsAt;
  final String? series;
  final String? venue;
  final bool hasPoster;
  final bool canManage;
  final int members;
  final String? coordinator;
  final String myRole;
  final int todo;
  final int inProgress;
  final int done;

  int get total => todo + inProgress + done;
}

class WorkspaceApi {
  const WorkspaceApi(this._client);
  final LaravelAuthApi _client;

  static const _path = '/api/v1/my/events';

  Future<WorkspaceDirectory> directory(int page, String search) async {
    final data = await _client.getAuthenticated(
      _path,
      query: {'page': '$page', if (search.isNotEmpty) 'q': search},
    );
    return _decode(() => WorkspaceDirectory._fromJson(data));
  }

  Future<WorkspaceSummary> summary(int eventId) async {
    final data = await _client.getAuthenticated('$_path/$eventId');
    return _decode(() => WorkspaceSummary._fromJson(data['data'] as _Json));
  }

  Future<MeetingAgenda> meetings(int eventId, int page, String search) async {
    final data = await _client.getAuthenticated(
      '$_path/$eventId/meetings',
      query: {'page': '$page', if (search.isNotEmpty) 'q': search},
    );
    return _decode(() => MeetingAgenda.fromJson(data));
  }

  Future<MeetingDetail> meeting(int eventId, int meetingId) async {
    final data = await _client.getAuthenticated(
      '$_path/$eventId/meetings/$meetingId',
    );
    return _decode(() => MeetingDetail.fromJson(data['data'] as _Json));
  }

  Future<CollisionReport> conflicts(int eventId, int page) async {
    final data = await _client.getAuthenticated(
      '$_path/$eventId/conflicts',
      query: {'page': '$page'},
    );
    return _decode(() => CollisionReport.fromJson(data));
  }

  Future<Uint8List> poster(int eventId) => _client.getPrivateImage(
    '/events/$eventId/poster',
    maxBytes: 2 * 1024 * 1024,
  );

  T _decode<T>(T Function() decode) {
    try {
      return decode();
    } on TypeError {
      throw const ApiFailure('Data workspace dari server tidak dapat dibaca.');
    } on FormatException {
      throw const ApiFailure(
        'Jadwal workspace dari server tidak dapat dibaca.',
      );
    }
  }
}
