import 'dart:async';

import 'package:material_ui/material_ui.dart';

import '../features/auth/auth_api.dart';
import '../features/auth/auth_session.dart';
import '../features/auth/login_screen.dart';
import '../features/legacy/legacy_api.dart';
import '../features/radar/radar_api.dart';
import '../features/tasks/tasks_api.dart';
import '../features/workspace/workspace_api.dart';
import '../features/workspace/workspace_shell.dart';
import 'evaloop_theme.dart';

class EvaloopApp extends StatefulWidget {
  const EvaloopApp({super.key, this.api, this.radarApi});

  final AuthApi? api;
  final RadarApi? radarApi;

  @override
  State<EvaloopApp> createState() => _EvaloopAppState();
}

class _EvaloopAppState extends State<EvaloopApp> {
  final _client = LaravelAuthApi();
  late final _radar = widget.radarApi ?? RadarApi(_client);
  late final _tasks = TasksApi(_client);
  late final _legacy = LegacyApi(_client);
  late final _workspace = WorkspaceApi(_client);
  late final AuthSession _session;
  late final AppLifecycleListener _lifecycle;

  @override
  void initState() {
    super.initState();
    _session = AuthSession(widget.api ?? _client);
    _lifecycle = AppLifecycleListener(
      onResume: () {
        if (_session.phase == SessionPhase.signedIn) {
          unawaited(_session.check());
        }
      },
    );
    unawaited(_session.check());
  }

  @override
  void dispose() {
    _lifecycle.dispose();
    _session.dispose();
    if (widget.api != null) _client.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => MaterialApp(
    title: 'Evaloop',
    debugShowCheckedModeBanner: false,
    theme: EvaloopTheme.light,
    home: ListenableBuilder(
      listenable: _session,
      builder: (context, child) => switch (_session.phase) {
        SessionPhase.checking ||
        SessionPhase.unavailable ||
        SessionPhase.signedOut => GuestShell(session: _session, radar: _radar),
        SessionPhase.signedIn => WorkspaceShell(
          session: _session,
          radar: _radar,
          tasks: _tasks,
          legacy: _legacy,
          workspace: _workspace,
          startOnRadar: !_session.signedInFromLogin,
        ),
      },
    ),
  );
}
