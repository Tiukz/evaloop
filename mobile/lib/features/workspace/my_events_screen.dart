import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../core/evaloop_time.dart';
import '../../shared/api_result.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_session.dart';
import '../legacy/legacy_api.dart';
import '../radar/radar_api.dart';
import '../tasks/tasks_api.dart';
import 'event_workspace_screen.dart';
import 'workspace_api.dart';
import 'workspace_status.dart';

class MyEventsScreen extends StatefulWidget {
  const MyEventsScreen({
    super.key,
    required this.api,
    required this.session,
    required this.tasks,
    required this.legacy,
    required this.onRadar,
    required this.radar,
  });

  final WorkspaceApi api;
  final AuthSession session;
  final TasksApi tasks;
  final LegacyApi legacy;
  final VoidCallback onRadar;
  final RadarApi radar;

  @override
  State<MyEventsScreen> createState() => _MyEventsScreenState();
}

class _MyEventsScreenState extends State<MyEventsScreen> {
  final _search = TextEditingController();
  final _scroll = ScrollController();
  late Future<WorkspaceDirectory> _events;
  String _query = '';
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _events = _fetch();
  }

  @override
  void dispose() {
    _search.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<WorkspaceDirectory> _fetch() =>
      readWithSession(widget.session, widget.api.directory(_page, _query));

  void _load(int page) => setState(() {
    _page = page;
    _events = _fetch();
  });

  void _submit() {
    FocusScope.of(context).unfocus();
    _query = _search.text.trim();
    _load(1);
  }

  void _open(WorkspaceEvent event) => Navigator.of(context).push<void>(
    MaterialPageRoute(
      builder: (_) => EventWorkspaceScreen(
        eventId: event.id,
        api: widget.api,
        session: widget.session,
        tasks: widget.tasks,
        legacy: widget.legacy,
        radar: widget.radar,
      ),
    ),
  );

  @override
  Widget build(BuildContext context) => RefreshIndicator(
    onRefresh: () async {
      _load(_page);
      try {
        await _events;
      } on Exception {
        // ApiResult presents the server or connection error.
      }
    },
    child: ListView(
      controller: _scroll,
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(14, 16, 14, 28),
      children: [
        Row(
          children: [
            const Expanded(
              child: Text('Event Saya', style: EvaloopTheme.heading),
            ),
            TextButton(
              onPressed: widget.onRadar,
              child: const Text('Event Radar'),
            ),
          ],
        ),
        const SizedBox(height: 14),
        ApiResult(
          future: _events,
          onRetry: () => _load(_page),
          content: _directory,
        ),
      ],
    ),
  );

  Widget _directory(WorkspaceDirectory directory) {
    final featured = directory.featured;
    if (featured == null) {
      return Container(
        padding: const EdgeInsets.all(16),
        decoration: _panel(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Belum ada workspace yang diizinkan',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 6),
            const Text(
              'Workspace tersedia untuk panitia event dan admin organisasi dengan keanggotaan yang berlaku.',
            ),
            TextButton(
              onPressed: widget.onRadar,
              child: const Text('Jelajahi Event Radar'),
            ),
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.all(16),
          decoration: _panel(
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [EvaloopTheme.ice, Colors.white, EvaloopTheme.aqua],
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Workspace utama',
                style: TextStyle(
                  color: EvaloopTheme.teal,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 10,
                runSpacing: 6,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  Text(
                    displayName(featured.title),
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  WorkspaceStatus(featured.status),
                ],
              ),
              const SizedBox(height: 8),
              _metadata(featured),
              const SizedBox(height: 12),
              FilledButton.icon(
                onPressed: () => _open(featured),
                icon: const Icon(Icons.arrow_forward, size: 16),
                iconAlignment: IconAlignment.end,
                label: const Text('Buka workspace'),
              ),
            ],
          ),
        ),
        const SizedBox(height: 23),
        Wrap(
          alignment: WrapAlignment.spaceBetween,
          runSpacing: 5,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            Text(
              'Daftar workspace',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            Text(
              '${directory.total} workspace dapat diakses',
              style: const TextStyle(fontSize: 12, color: EvaloopTheme.muted),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _search,
                textInputAction: TextInputAction.search,
                maxLength: 120,
                onSubmitted: (_) => _submit(),
                decoration: const InputDecoration(
                  counterText: '',
                  hintText: 'Cari event atau organisasi',
                  prefixIcon: Icon(Icons.search, size: 18),
                ),
              ),
            ),
            const SizedBox(width: 8),
            OutlinedButton(
              onPressed: _submit,
              style: _secondary(minWidth: 100),
              child: const Text('Cari'),
            ),
          ],
        ),
        const SizedBox(height: 14),
        if (directory.items.isEmpty)
          if (directory.total == 0)
            const Text('Tidak ada event yang cocok.')
          else
            Align(
              alignment: Alignment.centerLeft,
              child: TextButton(
                onPressed: () => _load(1),
                child: const Text('Ke halaman pertama'),
              ),
            )
        else
          DecoratedBox(
            decoration: _panel(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                for (final (index, event) in directory.items.indexed) ...[
                  if (index > 0)
                    const Divider(height: 1, color: EvaloopTheme.border),
                  _row(event),
                ],
              ],
            ),
          ),
        if (directory.lastPage > 1)
          Wrap(
            alignment: WrapAlignment.spaceBetween,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: [
              TextButton(
                onPressed: directory.page > 1
                    ? () => _paginate(directory.page - 1)
                    : null,
                child: const Text('Sebelumnya'),
              ),
              Text('${directory.page} / ${directory.lastPage}'),
              TextButton(
                onPressed: directory.page < directory.lastPage
                    ? () => _paginate(directory.page + 1)
                    : null,
                child: const Text('Berikutnya'),
              ),
            ],
          ),
      ],
    );
  }

  void _paginate(int page) {
    if (_scroll.hasClients) _scroll.jumpTo(0);
    _load(page);
  }

  Widget _row(WorkspaceEvent event) => Padding(
    padding: const EdgeInsets.all(14),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          displayName(event.title),
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 4),
        _metadata(event),
        const SizedBox(height: 8),
        WorkspaceStatus(event.status),
        const SizedBox(height: 6),
        OutlinedButton(
          onPressed: () => _open(event),
          style: _secondary(),
          child: const Text('Buka workspace'),
        ),
      ],
    ),
  );

  Widget _metadata(WorkspaceEvent event) {
    final date = EvaloopTime.wita(event.startsAt);
    return Wrap(
      spacing: 10,
      runSpacing: 3,
      children: [
        Text(
          displayName(event.organization),
          style: const TextStyle(color: EvaloopTheme.muted),
        ),
        Text(
          '${EvaloopTime.shortDate(date)} ${EvaloopTime.clock(date)} WITA',
          style: const TextStyle(color: EvaloopTheme.muted),
        ),
      ],
    );
  }

  BoxDecoration _panel({Gradient? gradient}) => BoxDecoration(
    color: Colors.white,
    gradient: gradient,
    border: Border.all(color: EvaloopTheme.border),
    borderRadius: BorderRadius.circular(16),
  );

  ButtonStyle _secondary({double minWidth = 0}) => OutlinedButton.styleFrom(
    foregroundColor: EvaloopTheme.ink,
    backgroundColor: Colors.white,
    minimumSize: Size(minWidth, 44),
    side: const BorderSide(color: EvaloopTheme.formBorder),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
  );
}
