import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../core/evaloop_time.dart';
import '../../shared/evaloop_widgets.dart';
import 'tasks_api.dart';

class TaskStatusPill extends StatelessWidget {
  const TaskStatusPill(this.status, {super.key});

  final TaskStatus? status;

  @override
  Widget build(BuildContext context) {
    final (ink, surface) = switch (status) {
      TaskStatus.inProgress => (
        const Color(0xFF286F91),
        const Color(0xFFEAF4F9),
      ),
      TaskStatus.done => (const Color(0xFF167347), const Color(0xFFEAF7EE)),
      _ => (EvaloopTheme.muted, const Color(0xFFEEF2F5)),
    };
    return DecoratedBox(
      decoration: BoxDecoration(
        color: surface,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 4),
        child: Text.rich(
          TextSpan(
            children: [
              TextSpan(
                text: '●  ',
                style: TextStyle(fontSize: 8, color: ink),
              ),
              TextSpan(text: status?.label ?? 'Status tidak tersedia'),
            ],
          ),
          semanticsLabel: status?.label ?? 'Status tidak tersedia',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w500,
            color: ink,
          ),
        ),
      ),
    );
  }
}

class TaskDeadline extends StatelessWidget {
  const TaskDeadline(this.task, {super.key});

  final PersonalTask task;

  @override
  Widget build(BuildContext context) {
    final dueAt = task.dueAt;
    if (dueAt == null) return const Text('Tanpa tenggat');
    final due = EvaloopTime.wita(dueAt);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(EvaloopTime.shortDate(due)),
        Text(
          '${EvaloopTime.clock(due)} WITA',
          style: const TextStyle(fontSize: 12, color: EvaloopTheme.muted),
        ),
        if (task.overdue)
          const Text(
            'Lewat tenggat',
            style: TextStyle(
              color: Color(0xFF986000),
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
      ],
    );
  }
}

class TaskField extends StatelessWidget {
  const TaskField(this.label, this.value, {super.key});
  final String label;
  final Widget value;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        label,
        style: const TextStyle(color: EvaloopTheme.muted, fontSize: 13),
      ),
      const SizedBox(height: 4),
      value,
    ],
  );
}

class TaskRow extends StatelessWidget {
  const TaskRow({super.key, required this.task, required this.onOpen});

  final PersonalTask task;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    final title = displayName(task.title);
    final source = task.sourceEventTitle;
    return DecoratedBox(
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: EvaloopTheme.border)),
      ),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(9, 12, 9, 14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Padding(
                  padding: EdgeInsets.only(top: 2),
                  child: Icon(
                    Icons.assignment_outlined,
                    size: 16,
                    color: EvaloopTheme.muted,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    title,
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ),
            if (source != null) ...[
              const SizedBox(height: 6),
              Text(
                'Sumber legacy: ${displayName(source)}',
                style: const TextStyle(fontSize: 12, color: EvaloopTheme.teal),
              ),
            ],
            const SizedBox(height: 3),
            Row(
              children: [
                Expanded(child: Wrap(children: [TaskStatusPill(task.status)])),
                TextButton.icon(
                  onPressed: onOpen,
                  iconAlignment: IconAlignment.end,
                  icon: const Icon(Icons.arrow_forward, size: 14),
                  label: Text('Detail', semanticsLabel: 'Detail $title'),
                  style: TextButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 8),
                    minimumSize: const Size(44, 44),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: TaskField(
                    'Event',
                    Text(displayName(task.event.title)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(child: TaskField('Tenggat', TaskDeadline(task))),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
