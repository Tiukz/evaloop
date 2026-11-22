import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../core/evaloop_time.dart';
import '../../shared/api_result.dart';
import '../../shared/evaloop_widgets.dart';
import '../../shared/private_picture.dart';
import '../auth/auth_session.dart';
import '../legacy/legacy_api.dart';
import '../legacy/legacy_screen.dart';
import '../meetings/meeting_hub_screen.dart';
import '../radar/radar_api.dart';
import '../radar/radar_detail_screen.dart';
import '../tasks/task_detail_screen.dart';
import '../tasks/tasks_api.dart';
import '../tasks/tasks_screen.dart';
import 'schedule_conflicts_screen.dart';
import 'workspace_api.dart';
import 'workspace_status.dart';

const _section = TextStyle(
  fontFamily: 'Geist',
  fontSize: 18,
  fontWeight: FontWeight.w600,
);

class EventWorkspaceScreen extends StatefulWidget {
  const EventWorkspaceScreen({
    super.key,
    required this.eventId,
    required this.api,
    required this.session,
    required this.tasks,
    required this.legacy,
    required this.radar,
  });

  final int eventId;
  final WorkspaceApi api;
  final AuthSession session;
  final TasksApi tasks;
  final LegacyApi legacy;
  final RadarApi radar;

  @override
  State<EventWorkspaceScreen> createState() => _EventWorkspaceScreenState();
}

class _EventWorkspaceScreenState extends State<EventWorkspaceScreen> {
  late final int? _account = widget.session.profile?.id;
  late Future<WorkspaceSummary> _summary;
  bool _showTasks = false;

  @override
  void initState() {
    super.initState();
    _summary = _fetch();
  }

  Future<WorkspaceSummary> _fetch() =>
      readWithSession(widget.session, widget.api.summary(widget.eventId));

  void _reload() => setState(() => _summary = _fetch());

  void _openLegacy() {
    final session = widget.session;
    final tasks = widget.tasks;
    Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => LegacyScreen(
          eventId: widget.eventId,
          api: widget.legacy,
          session: session,
          backLabel: 'Kembali ke workspace',
          onTask: (context, taskId) async {
            await Navigator.of(context).push<void>(
              MaterialPageRoute(
                builder: (_) => TaskDetailScreen(
                  taskId: taskId,
                  api: tasks,
                  session: session,
                ),
              ),
            );
          },
        ),
      ),
    );
  }

  void _openConflicts() {
    final radar = widget.radar;
    Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => ScheduleConflictsScreen(
          eventId: widget.eventId,
          api: widget.api,
          session: widget.session,
          onPublicEvent: (context, id) => Navigator.of(context).push<void>(
            MaterialPageRoute(
              builder: (_) => RadarDetailScreen(
                api: radar,
                eventId: id,
                reference: DateTime.now(),
                backLabel: 'Kembali ke Jadwal & bentrokan',
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _openMeetings() {
    Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => MeetingHubScreen(
          eventId: widget.eventId,
          api: widget.api,
          session: widget.session,
          tasks: widget.tasks,
        ),
      ),
    );
  }

  void _overview() {
    setState(() {
      _showTasks = false;
      _summary = _fetch();
    });
  }

  @override
  Widget build(BuildContext context) => PopScope(
    canPop: !_showTasks,
    onPopInvokedWithResult: (didPop, _) {
      if (!didPop && _showTasks) _overview();
    },
    child: Scaffold(
      body: SafeArea(
        child: ListenableBuilder(
          listenable: widget.session,
          builder: (context, _) {
            final session = widget.session;
            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(8, 4, 8, 0),
                  child: Align(
                    alignment: Alignment.centerLeft,
                    child: TextButton.icon(
                      onPressed: _showTasks
                          ? _overview
                          : () => Navigator.of(context).pop(),
                      label: Text(
                        _showTasks ? 'Kembali ke ringkasan' : 'Event Saya',
                      ),
                      icon: const Icon(Icons.arrow_back, size: 16),
                    ),
                  ),
                ),
                Expanded(
                  child: session.phase == SessionPhase.checking
                      ? const Align(
                          alignment: Alignment.topCenter,
                          child: LinearProgressIndicator(
                            semanticsLabel: 'Memeriksa sesi',
                          ),
                        )
                      : session.phase != SessionPhase.signedIn ||
                            session.profile?.id != _account
                      ? Padding(
                          padding: const EdgeInsets.all(14),
                          child: Align(
                            alignment: Alignment.topCenter,
                            child: InlineNotice(
                              session.message ?? 'Silakan masuk kembali untuk membuka workspace.',
                            ),
                          ),
                        )
                      : ApiResult(
                          future: _summary,
                          onRetry: _reload,
                          content: (summary) => _showTasks
                              ? _tasks(summary)
                              : _ringkasan(summary),
                        ),
                ),
              ],
            );
          },
        ),
      ),
    ),
  );

  Widget _tasks(WorkspaceSummary summary) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Padding(
        padding: const EdgeInsets.fromLTRB(14, 4, 14, 6),
        child: Text(
          displayName(summary.event.title),
          style: _section,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
        ),
      ),
      Expanded(
        child: TasksScreen(
          api: widget.tasks,
          session: widget.session,
          workspaceEventId: summary.event.id,
        ),
      ),
    ],
  );

  Widget _ringkasan(WorkspaceSummary summary) {
    final event = summary.event;
    final start = EvaloopTime.wita(event.startsAt);
    final end = EvaloopTime.wita(summary.endsAt);
    final date = EvaloopTime.sameDay(start, end)
        ? EvaloopTime.shortDate(start)
        : '${EvaloopTime.shortDate(start)} – ${EvaloopTime.shortDate(end)}';
    final fallback = _PosterFallback(
      title: displayName(event.title),
      year: start.year,
    );
    final series = summary.series;
    return ListView(
      padding: const EdgeInsets.fromLTRB(14, 6, 14, 28),
      children: [
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [EvaloopTheme.ice, Colors.white],
            ),
            border: Border.all(color: EvaloopTheme.border),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox.square(
                dimension: 112,
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: summary.hasPoster
                      ? PrivatePicture(
                          name: 'Poster ${displayName(event.title)}',
                          fallback: fallback,
                          load: () => PrivatePicture.decode(
                            readWithSession(
                              widget.session,
                              widget.api.poster(event.id),
                            ),
                          ),
                        )
                      : fallback,
                ),
              ),
              const SizedBox(height: 14),
              Text(displayName(event.title), style: EvaloopTheme.heading),
              const SizedBox(height: 3),
              Text(
                displayName(event.organization),
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
              if (series != null)
                Text(
                  '${displayName(series)} · Edisi ${start.year}',
                  style: const TextStyle(color: EvaloopTheme.muted),
                )
              else
                Text(
                  'Edisi ${start.year}',
                  style: const TextStyle(color: EvaloopTheme.muted),
                ),
              const SizedBox(height: 12),
              WorkspaceStatus(event.status),
              const SizedBox(height: 8),
              _Fact(
                Icons.event_outlined,
                '$date · ${EvaloopTime.clock(start)}–${EvaloopTime.clock(end)} WITA',
              ),
              const SizedBox(height: 4),
              _Fact(
                Icons.place_outlined,
                summary.venue?.trim().isNotEmpty == true
                    ? displayName(summary.venue!)
                    : 'Lokasi belum ditentukan',
              ),
            ],
          ),
        ),
        const SizedBox(height: 8),
        Semantics(
          label: 'Navigasi workspace',
          child: Wrap(
            spacing: 4,
            runSpacing: 3,
            children: [
              _tab(
                'Ringkasan',
                Icons.home_outlined,
                selected: true,
                action: _reload,
              ),
              if (summary.canManage)
                _tab('Jadwal & bentrokan', Icons.radar, action: _openConflicts),
              _tab('Meeting Hub', Icons.event_outlined, action: _openMeetings),
              _tab(
                'Tugas & Progres',
                Icons.assignment_outlined,
                action: () => setState(() => _showTasks = true),
              ),
              _tab(
                'Legacy Insights',
                Icons.menu_book_outlined,
                action: _openLegacy,
              ),
            ],
          ),
        ),
        const Divider(color: EvaloopTheme.border),
        const SizedBox(height: 10),
        const Text('Progres tugas event', style: _section),
        const SizedBox(height: 8),
        if (summary.total == 0)
          const Text('Belum ada tugas untuk event ini.')
        else ...[
          LinearProgressIndicator(
            value: summary.done / summary.total,
            semanticsLabel: 'Progres tugas event',
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 12,
            runSpacing: 6,
            children: [
              Text('${summary.todo} Belum dimulai'),
              Text('${summary.inProgress} Sedang dikerjakan'),
              Text('${summary.done} Selesai'),
            ],
          ),
        ],
        const SizedBox(height: 20),
        const Text('Kepanitiaan', style: _section),
        const SizedBox(height: 6),
        Text('${summary.members} anggota panitia'),
        if (summary.coordinator case final coordinator?)
          Text('Ketua panitia · ${displayName(coordinator)}'),
        Text(switch (summary.myRole) {
          'coordinator' => 'Peran Anda · Ketua panitia',
          'member' => 'Peran Anda · Anggota panitia',
          'organization_admin' => 'Peran Anda · Admin organisasi',
          _ => 'Peran Anda · ${summary.myRole}',
        }, style: const TextStyle(color: EvaloopTheme.muted)),
      ],
    );
  }

  Widget _tab(
    String label,
    IconData icon, {
    VoidCallback? action,
    bool selected = false,
  }) {
    final content = TextButton.icon(
      onPressed: action,
      icon: Icon(icon, size: 16),
      label: Text(label),
      style: TextButton.styleFrom(
        minimumSize: const Size(0, 48),
        padding: const EdgeInsets.symmetric(horizontal: 9),
        backgroundColor: selected ? EvaloopTheme.mist : null,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
    );
    return Semantics(selected: selected, child: content);
  }
}

class _PosterFallback extends StatelessWidget {
  const _PosterFallback({required this.title, required this.year});
  final String title;
  final int year;

  @override
  Widget build(BuildContext context) => ExcludeSemantics(
    child: ColoredBox(
      color: EvaloopTheme.teal,
      child: Padding(
        padding: const EdgeInsets.all(9),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'evaloop.',
              style: TextStyle(
                fontFamily: 'Manrope',
                color: Colors.white,
                fontSize: 11,
              ),
            ),
            Expanded(
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  title,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontFamily: 'Geist',
                    color: Colors.white,
                    fontSize: 13,
                    height: 1.1,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
            Text(
              '$year',
              style: const TextStyle(fontSize: 10, color: Colors.white),
            ),
          ],
        ),
      ),
    ),
  );
}

class _Fact extends StatelessWidget {
  const _Fact(this.icon, this.text);
  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) => Row(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Padding(
        padding: const EdgeInsets.only(top: 2),
        child: Icon(icon, size: 16, color: EvaloopTheme.teal),
      ),
      const SizedBox(width: 8),
      Expanded(
        child: Text(text, style: const TextStyle(color: EvaloopTheme.muted)),
      ),
    ],
  );
}
