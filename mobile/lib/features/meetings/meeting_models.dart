import '../tasks/tasks_api.dart';

typedef _Json = Map<String, dynamic>;

class MeetingAgenda {
  MeetingAgenda.fromJson(Map<String, dynamic> data)
    : event = MeetingEvent._fromJson(data['event'] as _Json),
      referenceTime = DateTime.parse(data['reference_time'] as String),
      items = (data['data'] as List<dynamic>)
          .map((item) => MeetingItem._fromJson(item as _Json))
          .toList(),
      page = (data['meta'] as _Json)['current_page'] as int,
      lastPage = (data['meta'] as _Json)['last_page'] as int,
      total = (data['meta'] as _Json)['total'] as int;

  final MeetingEvent event;
  final DateTime referenceTime;
  final List<MeetingItem> items;
  final int page;
  final int lastPage;
  final int total;
}

class MeetingEvent {
  MeetingEvent._fromJson(_Json data)
    : title = data['title'] as String,
      status = data['status'] as String;

  final String title;
  final String status;
}

class MeetingItem {
  MeetingItem._fromJson(_Json data)
    : id = data['id'] as int,
      title = data['title'] as String,
      scheduledAt = DateTime.parse(data['scheduled_at'] as String),
      notesExcerpt = data['notes_excerpt'] as String,
      decisionsCount = data['decisions_count'] as int;

  final int id;
  final String title;
  final DateTime scheduledAt;
  final String notesExcerpt;
  final int decisionsCount;
}

class MeetingDetail {
  MeetingDetail.fromJson(Map<String, dynamic> data)
    : id = data['id'] as int,
      event = MeetingEvent._fromJson(data['event'] as _Json),
      title = data['title'] as String,
      scheduledAt = DateTime.parse(data['scheduled_at'] as String),
      notes = data['notes'] as String?,
      decisions = (data['decisions'] as List<dynamic>)
          .map((item) => MeetingDecision._fromJson(item as _Json))
          .toList();

  final int id;
  final MeetingEvent event;
  final String title;
  final DateTime scheduledAt;
  final String? notes;
  final List<MeetingDecision> decisions;
}

class MeetingDecision {
  MeetingDecision._fromJson(_Json data)
    : summary = data['summary'] as String,
      rationale = data['rationale'] as String?,
      tasks = (data['tasks'] as List<dynamic>)
          .map((item) => MeetingTask._fromJson(item as _Json))
          .toList();

  final String summary;
  final String? rationale;
  final List<MeetingTask> tasks;
}

class MeetingTask {
  MeetingTask._fromJson(_Json data)
    : id = data['id'] as int,
      title = data['title'] as String,
      status = TaskStatus.parse(data['status']);

  final int id;
  final String title;
  final TaskStatus? status;
}
