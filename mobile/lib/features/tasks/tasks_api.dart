import '../auth/auth_api.dart';

typedef _Json = Map<String, dynamic>;

enum TaskStatus {
  todo('todo', 'Belum dimulai'),
  inProgress('in_progress', 'Sedang dikerjakan'),
  done('done', 'Selesai');

  const TaskStatus(this.value, this.label);

  final String value;
  final String label;

  static TaskStatus? parse(Object? value) =>
      values.where((status) => status.value == value).firstOrNull;
}

enum TaskView {
  all('Semua tugas'),
  active('Aktif'),
  done('Selesai');

  const TaskView(this.label);
  final String label;
}

enum TaskSort {
  nearest('deadline_asc', 'Terdekat dahulu'),
  farthest('deadline_desc', 'Terjauh dahulu');

  const TaskSort(this.value, this.label);

  final String value;
  final String label;
}

class PersonalTask {
  PersonalTask._fromJson(_Json json)
    : id = json['id'] as int,
      title = json['title'] as String,
      status = TaskStatus.parse(json['status']),
      dueAt = _parseDate(json['due_at'] as String?),
      event = TaskEvent._fromJson(json['event'] as _Json),
      sourceEventTitle = json['source_event_title'] as String?,
      decisionSummary = json['decision_summary'] as String?,
      assigneeName = json['assignee_name'] as String?,
      canUpdate = json['can_update_status'] == true;

  final int id;
  final String title;
  final TaskStatus? status;
  final DateTime? dueAt;
  final TaskEvent event;
  final String? sourceEventTitle;
  final String? decisionSummary;
  final bool canUpdate;
  final String? assigneeName;

  bool get overdue {
    final deadline = dueAt;
    return status != null &&
        status != TaskStatus.done &&
        deadline != null &&
        deadline.isBefore(DateTime.now());
  }

  static DateTime? _parseDate(String? value) =>
      value == null ? null : DateTime.parse(value);
}

class TaskEvent {
  TaskEvent._fromJson(_Json json)
    : id = json['id'] as int,
      title = json['title'] as String;

  final int id;
  final String title;
}

class TaskPage {
  TaskPage._fromJson(_Json json)
    : tasks = (json['data'] as List)
          .map((value) => PersonalTask._fromJson(value as _Json))
          .toList(),
      events = ((json['task_filters'] as _Json)['events'] as List)
          .map((value) => TaskEvent._fromJson(value as _Json))
          .toList(),
      page = (json['meta'] as _Json)['current_page'] as int,
      lastPage = (json['meta'] as _Json)['last_page'] as int,
      total = (json['meta'] as _Json)['total'] as int;

  final List<PersonalTask> tasks;
  final List<TaskEvent> events;
  final int page;
  final int lastPage;
  final int total;
}

class TasksApi {
  const TasksApi(this._client);

  final LaravelAuthApi _client;
  static const _path = '/api/v1/my/tasks';

  Future<TaskPage> list(Map<String, String> query) async {
    final json = await _client.getAuthenticated(_path, query: query);
    return _decode(() => TaskPage._fromJson(json));
  }

  Future<PersonalTask> detail(int id) async {
    final json = await _client.getAuthenticated('$_path/$id');
    return _decode(() => PersonalTask._fromJson(json['data'] as _Json));
  }

  Future<PersonalTask> meetingTask(
    int eventId,
    int meetingId,
    int taskId,
  ) async {
    final json = await _client.getAuthenticated(
      '/api/v1/my/events/$eventId/meetings/$meetingId/tasks/$taskId',
    );
    return _decode(() => PersonalTask._fromJson(json['data'] as _Json));
  }

  Future<PersonalTask> update(int id, TaskStatus status) async {
    final json = await _client.patchAuthenticated('$_path/$id/status', {
      'status': status.value,
    });
    return _decode(() => PersonalTask._fromJson(json['data'] as _Json));
  }

  T _decode<T>(T Function() parse) {
    try {
      return parse();
    } on TypeError {
      throw const ApiFailure('Data tugas dari server tidak dapat dibaca.');
    } on FormatException {
      throw const ApiFailure('Tenggat tugas dari server tidak dapat dibaca.');
    }
  }
}
