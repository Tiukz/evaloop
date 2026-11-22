class CollisionReport {
  CollisionReport.fromJson(Map<String, dynamic> data)
    : event = CollisionEvent._fromJson(data['event'] as Map<String, dynamic>),
      targets =
          ((data['event'] as Map<String, dynamic>)['target_programs']
                  as List<dynamic>)
              .cast<String>(),
      warnings = (data['warnings'] as List<dynamic>)
          .map(
            (entry) =>
                CollisionWarning._fromJson(entry as Map<String, dynamic>),
          )
          .toList(),
      unavailableReason = data['unavailable_reason'] as String?,
      total = (data['meta'] as Map<String, dynamic>)['total'] as int,
      page = (data['meta'] as Map<String, dynamic>)['current_page'] as int,
      lastPage = (data['meta'] as Map<String, dynamic>)['last_page'] as int;

  final CollisionEvent event;
  final List<String> targets;
  final List<CollisionWarning> warnings;
  final String? unavailableReason;
  final int total;
  final int page;
  final int lastPage;
}

class CollisionEvent {
  CollisionEvent._fromJson(Map<String, dynamic> data)
    : id = data['id'] as int,
      title = data['title'] as String,
      status = data['status'] as String,
      venue = data['venue'] as String?,
      startsAt = _date(data['starts_at'] as String?),
      endsAt = _date(data['ends_at'] as String?);

  final int id;
  final String title;
  final String status;
  final String? venue;
  final DateTime? startsAt;
  final DateTime? endsAt;

  static DateTime? _date(String? value) =>
      value == null ? null : DateTime.parse(value);
}

class CollisionWarning {
  CollisionWarning._fromJson(Map<String, dynamic> data)
    : event = CollisionEvent._fromJson(data['event'] as Map<String, dynamic>),
      reasons = (data['reasons'] as List<dynamic>)
          .map(
            (entry) => CollisionReason._fromJson(entry as Map<String, dynamic>),
          )
          .toList();

  final CollisionEvent event;
  final List<CollisionReason> reasons;
}

class CollisionReason {
  CollisionReason._fromJson(Map<String, dynamic> data)
    : type = data['type'] as String,
      label = data['label'] as String,
      description = data['description'] as String;

  final String type;
  final String label;
  final String description;
}
