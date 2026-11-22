import 'dart:math' as math;

import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_session.dart';
import '../legacy/legacy_api.dart';
import '../radar/radar_api.dart';
import '../radar/radar_screen.dart';
import '../tasks/tasks_api.dart';
import '../tasks/tasks_screen.dart';
import 'my_events_screen.dart';
import 'workspace_api.dart';

enum _Page {
  dashboard('Dashboard', Icons.home_outlined),
  tasks('Tugas Saya', Icons.assignment_outlined),
  events('Event Saya', Icons.event_outlined),
  radar('Event Radar', Icons.radar),
  profile('Profil', Icons.person_outline);

  const _Page(this.label, this.icon);

  final String label;
  final IconData icon;
}

class WorkspaceShell extends StatefulWidget {
  const WorkspaceShell({
    super.key,
    required this.session,
    required this.radar,
    required this.tasks,
    required this.legacy,
    required this.workspace,
    required this.startOnRadar,
  });

  final AuthSession session;
  final RadarApi radar;
  final TasksApi tasks;
  final LegacyApi legacy;
  final WorkspaceApi workspace;
  final bool startOnRadar;

  @override
  State<WorkspaceShell> createState() => _WorkspaceShellState();
}

class _WorkspaceShellState extends State<WorkspaceShell> {
  late _Page _page = widget.startOnRadar ? _Page.radar : _Page.dashboard;

  void _select(_Page page) => setState(() => _page = page);

  @override
  Widget build(BuildContext context) {
    final session = widget.session;
    final profile = session.profile!;
    final message = widget.session.message;

    return Scaffold(
      drawer: Drawer(
        width: math.min(304.0, MediaQuery.sizeOf(context).width - 32),
        backgroundColor: EvaloopTheme.ice,
        child: Builder(
          builder: (drawerContext) {
            void open(_Page page) {
              Navigator.of(drawerContext).pop();
              _select(page);
            }

            Widget item(_Page page) => ListTile(
              leading: Icon(page.icon, size: 20),
              title: Text(page.label),
              selected: _page == page,
              selectedColor: EvaloopTheme.teal,
              selectedTileColor: EvaloopTheme.mist,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
              onTap: () => open(page),
            );

            return SafeArea(
              child: LayoutBuilder(
                builder: (context, constraints) => SingleChildScrollView(
                  padding: const EdgeInsets.all(14),
                  child: ConstrainedBox(
                    constraints: BoxConstraints(
                      minHeight: math.max(0.0, constraints.maxHeight - 28),
                    ),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Row(
                              children: [
                                const Expanded(child: EvaloopWordmark()),
                                IconButton(
                                  tooltip: 'Tutup navigasi',
                                  onPressed: () =>
                                      Navigator.of(drawerContext).pop(),
                                  icon: const Icon(Icons.close),
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),
                            const _GroupLabel('Pribadi'),
                            item(_Page.dashboard),
                            item(_Page.tasks),
                            const SizedBox(height: 12),
                            const _GroupLabel('Koordinasi'),
                            item(_Page.events),
                            const SizedBox(height: 12),
                            const _GroupLabel('Jelajahi'),
                            item(_Page.radar),
                            const SizedBox(height: 18),
                          ],
                        ),
                        DecoratedBox(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            border: Border.all(color: EvaloopTheme.border),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: ExpansionTile(
                            shape: const Border(),
                            collapsedShape: const Border(),
                            leading: const Icon(Icons.account_circle_outlined),
                            title: const Text('Akun pribadi'),
                            children: [
                              Padding(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                ),
                                child: Text(
                                  profile.email,
                                  style: const TextStyle(
                                    color: EvaloopTheme.muted,
                                  ),
                                ),
                              ),
                              item(_Page.profile),
                              ListTile(
                                leading: const Icon(Icons.logout),
                                title: const Text('Keluar'),
                                enabled: !widget.session.busy,
                                onTap: () {
                                  Navigator.of(drawerContext).pop();
                                  widget.session.signOut();
                                },
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          },
        ),
      ),
      body: SafeArea(
        child: Column(
          children: [
            ColoredBox(
              color: EvaloopTheme.ice,
              child: Builder(
                builder: (context) => Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 6,
                  ),
                  child: Row(
                    children: [
                      IconButton(
                        tooltip: 'Buka navigasi',
                        onPressed: () => Scaffold.of(context).openDrawer(),
                        icon: const Icon(Icons.menu),
                      ),
                      const SizedBox(width: 4),
                      const Expanded(child: EvaloopWordmark()),
                    ],
                  ),
                ),
              ),
            ),
            DecoratedBox(
              decoration: const BoxDecoration(
                color: Colors.white,
                border: Border.symmetric(
                  horizontal: BorderSide(color: EvaloopTheme.border),
                ),
              ),
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 4,
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Ruang kerja / ${_page.label}',
                        style: const TextStyle(fontSize: 13),
                      ),
                    ),
                    if (_page != _Page.radar)
                      IconButton(
                        tooltip: 'Buka Event Radar',
                        onPressed: () => _select(_Page.radar),
                        icon: const Icon(Icons.radar, size: 19),
                      ),
                  ],
                ),
              ),
            ),
            Expanded(
              child: switch (_page) {
                _Page.radar => RadarScreen(api: widget.radar),
                _Page.tasks => TasksScreen(
                  api: widget.tasks,
                  session: widget.session,
                ),
                _Page.events => MyEventsScreen(
                  api: widget.workspace,
                  session: widget.session,
                  legacy: widget.legacy,
                  tasks: widget.tasks,
                  onRadar: () => _select(_Page.radar),
                  radar: widget.radar,
                ),
                _Page.dashboard || _Page.profile => ListView(
                  key: ValueKey(_page),
                  padding: const EdgeInsets.all(14),
                  children: [
                    if (message != null) ...[
                      InlineNotice(message),
                      const SizedBox(height: 12),
                    ],
                    if (_page == _Page.dashboard) _dashboard() else _profile(),
                  ],
                ),
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _dashboard() => DecoratedBox(
    decoration: BoxDecoration(
      color: Colors.white,
      border: Border.all(color: EvaloopTheme.border),
      borderRadius: BorderRadius.circular(12),
    ),
    child: Padding(
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Selamat datang kembali.', style: EvaloopTheme.heading),
          const SizedBox(height: 6),
          Text(displayName(widget.session.profile!.name)),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: () => _select(_Page.tasks),
            child: const Text('Lihat tugas saya'),
          ),
        ],
      ),
    ),
  );

  Widget _profile() {
    final session = widget.session;
    final profile = session.profile!;
    final program = profile.studyProgram;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Profil', style: EvaloopTheme.heading),
        const SizedBox(height: 12),
        Card(
          elevation: 0,
          color: Colors.white,
          shape: RoundedRectangleBorder(
            side: const BorderSide(color: EvaloopTheme.border),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Padding(
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  displayName(profile.name),
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 4),
                Text(profile.email),
                if (program != null && program.isNotEmpty) ...[
                  const SizedBox(height: 14),
                  const Text(
                    'Program studi',
                    style: TextStyle(color: EvaloopTheme.muted),
                  ),
                  Text(program),
                ],
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),
        OutlinedButton.icon(
          onPressed: session.busy ? null : session.signOut,
          icon: const Icon(Icons.logout, size: 18),
          label: Text(session.busy ? 'Memproses…' : 'Keluar'),
        ),
      ],
    );
  }
}

class _GroupLabel extends StatelessWidget {
  const _GroupLabel(this.label);

  final String label;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.fromLTRB(16, 4, 16, 3),
    child: Text(
      label,
      style: const TextStyle(
        fontSize: 13,
        color: EvaloopTheme.muted,
        fontWeight: FontWeight.w600,
      ),
    ),
  );
}
