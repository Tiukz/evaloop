import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../core/evaloop_time.dart';
import '../../shared/api_result.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_session.dart';
import '../tasks/task_detail_screen.dart';
import '../tasks/task_widgets.dart';
import '../tasks/tasks_api.dart';
import '../workspace/workspace_api.dart';
import 'meeting_models.dart';
import 'meeting_page.dart';

class MeetingDetailScreen extends StatefulWidget {
  const MeetingDetailScreen({
    super.key,
    required this.eventId,
    required this.meetingId,
    required this.api,
    required this.tasks,
    required this.session,
  });

  final int eventId;
  final int meetingId;
  final WorkspaceApi api;
  final TasksApi tasks;
  final AuthSession session;

  @override
  State<MeetingDetailScreen> createState() => _MeetingDetailScreenState();
}

class _MeetingDetailScreenState extends State<MeetingDetailScreen> {
  final _decisions = GlobalKey();
  late final int? _account;
  late Future<MeetingDetail> _detail;

  @override
  void initState() {
    super.initState();
    _account = widget.session.profile?.id;
    _detail = _fetch();
  }

  Future<MeetingDetail> _fetch() => readWithSession(
    widget.session,
    widget.api.meeting(widget.eventId, widget.meetingId),
  );

  Future<void> _reload() async {
    setState(() {
      _detail = _fetch();
    });
    try {
      await _detail;
    } on Exception {
      // ApiResult shows the error and retry.
    }
  }

  void _openTask(MeetingTask task) => Navigator.of(context).push<void>(
    MaterialPageRoute(
      builder: (_) => TaskDetailScreen(
        taskId: task.id,
        api: widget.tasks,
        session: widget.session,
        backLabel: 'Kembali ke catatan rapat',
        loadReadOnly: () =>
            widget.tasks.meetingTask(widget.eventId, widget.meetingId, task.id),
      ),
    ),
  );

  @override
  Widget build(BuildContext context) => MeetingPage(
    session: widget.session,
    accountId: _account,
    backLabel: 'Kembali ke daftar rapat',
    future: _detail,
    reload: _reload,
    content: _content,
  );

  Widget _content(MeetingDetail detail) {
    final date = EvaloopTime.wita(detail.scheduledAt);
    final notes = displayName(detail.notes ?? '');
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        MeetingEventContext(detail.event),
        const SizedBox(height: 20),
        Text(displayName(detail.title), style: EvaloopTheme.heading),
        const SizedBox(height: 4),
        Text(
          '${EvaloopTime.longDate(date)} · ${EvaloopTime.clock(date)} WITA',
          style: const TextStyle(color: EvaloopTheme.muted),
        ),
        const SizedBox(height: 22),
        _section([
          const Text('Catatan rapat', style: meetingHeading),
          const SizedBox(height: 12),
          if (notes.trim().isEmpty)
            const Text(
              'Belum ada catatan.',
              style: TextStyle(color: EvaloopTheme.muted),
            )
          else
            SelectableText(
              notes,
              style: const TextStyle(
                fontSize: 16,
                height: 1.7,
                color: EvaloopTheme.ink,
              ),
            ),
          if (notes.length > 1200)
            Align(
              alignment: Alignment.centerLeft,
              child: TextButton(
                onPressed: () {
                  final target = _decisions.currentContext;
                  if (target != null) {
                    Scrollable.ensureVisible(target, alignment: .04);
                  }
                },
                child: const Text('Ke keputusan rapat'),
              ),
            ),
        ]),
        const SizedBox(height: 22),
        _section([
          Text('Keputusan rapat', key: _decisions, style: meetingHeading),
          const SizedBox(height: 14),
          if (detail.decisions.isEmpty)
            const Text(
              'Belum ada keputusan.',
              style: TextStyle(color: EvaloopTheme.muted),
            ),
          for (final (index, decision) in detail.decisions.indexed) ...[
            if (index > 0) ...[
              const SizedBox(height: 16),
              const Divider(height: 1, color: EvaloopTheme.border),
              const SizedBox(height: 16),
            ],
            _decision(decision),
          ],
        ]),
      ],
    );
  }

  Widget _decision(MeetingDecision decision) {
    final rationale = decision.rationale?.trim();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          displayName(decision.summary),
          style: const TextStyle(
            fontFamily: 'Geist',
            fontSize: 16,
            fontWeight: FontWeight.w600,
            height: 1.4,
          ),
        ),
        if (rationale != null && rationale.isNotEmpty) ...[
          const SizedBox(height: 6),
          Text(
            displayName(rationale),
            style: const TextStyle(height: 1.6, color: EvaloopTheme.muted),
          ),
        ],
        const SizedBox(height: 12),
        const Text(
          'Tugas terkait',
          style: TextStyle(color: meetingCyan, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 6),
        if (decision.tasks.isEmpty)
          const Text(
            'Belum ada tugas tindak lanjut.',
            style: TextStyle(color: EvaloopTheme.muted),
          )
        else
          for (final task in decision.tasks)
            Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Material(
                color: EvaloopTheme.ice,
                borderRadius: BorderRadius.circular(8),
                child: InkWell(
                  borderRadius: BorderRadius.circular(8),
                  onTap: () => _openTask(task),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(minHeight: 48),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 8,
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  displayName(task.title),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                if (task.status != null) ...[
                                  const SizedBox(height: 4),
                                  TaskStatusPill(task.status),
                                ],
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          const Icon(
                            Icons.arrow_forward,
                            size: 15,
                            color: meetingCyan,
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
      ],
    );
  }

  Widget _section(List<Widget> children) => Material(
    color: Colors.white,
    shape: meetingBorder(),
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: children,
      ),
    ),
  );
}
