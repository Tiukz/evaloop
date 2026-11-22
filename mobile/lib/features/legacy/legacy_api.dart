import 'dart:typed_data';

import '../auth/auth_api.dart';
import '../tasks/tasks_api.dart';

typedef _Json = Map<String, dynamic>;

class LegacyEvent {
  LegacyEvent._fromJson(_Json json)
    : id = json['id'] as int,
      title = json['title'] as String,
      startsAt = DateTime.parse(json['starts_at'] as String),
      series = json['series'] as String?;

  final int id;
  final String title;
  final DateTime startsAt;
  final String? series;
}

class LegacyImage {
  LegacyImage._fromJson(_Json json)
    : id = json['id'] as int,
      name = json['name'] as String,
      size = json['size_bytes'] as int;

  final int id;
  final String name;
  final int size;
}

class LegacyFollowUp {
  LegacyFollowUp._fromJson(_Json json)
    : title = json['title'] as String,
      status = TaskStatus.parse(json['status']),
      dueAt = _date(json['due_at'] as String?),
      personalTaskId = json['personal_task_id'] as int?;

  final String title;
  final TaskStatus? status;
  final DateTime? dueAt;
  final int? personalTaskId;

  static DateTime? _date(String? value) =>
      value == null ? null : DateTime.parse(value);
}

class LegacyLesson {
  LegacyLesson._fromJson(_Json json)
    : id = json['id'] as int,
      problem = json['problem'] as String,
      recommendation = json['recommendation'] as String,
      source = LegacyEvent._fromJson(json['source_event'] as _Json),
      author = json['author'] as String,
      approvedAt = DateTime.parse(json['approved_at'] as String),
      followUp = switch (json['follow_up']) {
        final _Json value => LegacyFollowUp._fromJson(value),
        _ => null,
      },
      canCreateFollowUp = json['can_create_follow_up'] == true,
      images =
          (json['images'] as List<dynamic>?)
              ?.map((item) => LegacyImage._fromJson(item as _Json))
              .toList() ??
          const [];

  final int id;
  final String problem;
  final String recommendation;
  final LegacyEvent source;
  final String author;
  final DateTime approvedAt;
  final LegacyFollowUp? followUp;
  final List<LegacyImage> images;
  final bool canCreateFollowUp;
}

class LegacyPage<T> {
  LegacyPage._fromJson(_Json json, T Function(_Json) decode)
    : items = (json['data'] as List<dynamic>)
          .map((item) => decode(item as _Json))
          .toList(),
      page = (json['meta'] as _Json)['current_page'] as int,
      lastPage = (json['meta'] as _Json)['last_page'] as int;

  final List<T> items;
  final int page;
  final int lastPage;
}

class LegacyCollection {
  LegacyCollection._fromJson(_Json json)
    : event = LegacyEvent._fromJson(json['event'] as _Json),
      lessons = LegacyPage._fromJson(json, LegacyLesson._fromJson),
      inherited = (json['summary'] as _Json)['inherited'] as int,
      activeFollowUps = (json['summary'] as _Json)['active_follow_ups'] as int;

  final LegacyEvent event;
  final LegacyPage<LegacyLesson> lessons;
  final int inherited;
  final int activeFollowUps;
}

class LegacyApi {
  const LegacyApi(this._client);

  final LaravelAuthApi _client;
  static const _events = '/api/v1/my/events';

  Future<LegacyCollection> lessons(int eventId, int page, String query) async {
    final json = await _client.getAuthenticated(
      '$_events/$eventId/legacy',
      query: {'page': '$page', if (query.isNotEmpty) 'q': query},
    );
    return _decode(() => LegacyCollection._fromJson(json));
  }

  Future<LegacyLesson> lesson(int eventId, int lessonId) async {
    final json = await _client.getAuthenticated(
      '$_events/$eventId/legacy/$lessonId',
    );
    return _decode(() => LegacyLesson._fromJson(json['data'] as _Json));
  }

  String _action(int eventId, int lessonId) =>
      '$_events/$eventId/legacy/$lessonId/action';

  Future<LegacyActionForm> actionForm(int eventId, int lessonId) async {
    final json = await _client.getAuthenticated(_action(eventId, lessonId));
    return _decode(() => LegacyActionForm._fromJson(json['data'] as _Json));
  }

  Future<({String title, String description})> draft(
    int eventId,
    int lessonId,
  ) async {
    final json = await _client.postAuthenticated(
      '${_action(eventId, lessonId)}/draft',
    );
    return _decode(() {
      final draft = json['draft'] as _Json;
      return (
        title: draft['title'] as String,
        description: draft['description'] as String,
      );
    });
  }

  Future<bool> save(
    int eventId,
    int lessonId, {
    required String title,
    required int assigneeId,
    required String dueAt,
  }) async {
    final json = await _client.postAuthenticated(_action(eventId, lessonId), {
      'title': title,
      'assignee_user_id': '$assigneeId',
      'due_at': dueAt,
    });
    return _decode(() => (json['data'] as _Json)['created'] as bool);
  }

  Future<Uint8List> image(
    int eventId,
    int lessonId,
    LegacyImage image,
  ) => _client.getPrivateImage(
    '/workspace/events/$eventId/legacy/sources/$lessonId/attachments/${image.id}/preview',
    maxBytes: image.size,
    expectedSize: image.size,
  );

  T _decode<T>(T Function() parse) {
    try {
      return parse();
    } on TypeError {
      throw const ApiFailure(
        'Data Legacy Insights dari server tidak dapat dibaca.',
      );
    } on FormatException {
      throw const ApiFailure(
        'Tanggal evaluasi dari server tidak dapat dibaca.',
      );
    }
  }
}

class LegacyActionForm {
  LegacyActionForm._fromJson(_Json json)
    : event = LegacyEvent._fromJson(json['event'] as _Json),
      members = (json['members'] as List<dynamic>)
          .cast<_Json>()
          .map(
            (member) =>
                (id: member['id'] as int, name: member['name'] as String),
          )
          .toList();

  final LegacyEvent event;
  final List<({int id, String name})> members;
}
