import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_api.dart';
import '../auth/auth_session.dart';
import 'task_widgets.dart';
import 'tasks_api.dart';

class TaskDetailScreen extends StatefulWidget {
  const TaskDetailScreen({
    super.key,
    required this.taskId,
    required this.api,
    required this.session,
    this.loadReadOnly,
    this.backLabel = 'Kembali ke Tugas Saya',
  });

  final int taskId;
  final TasksApi api;
  final AuthSession session;
  final Future<PersonalTask> Function()? loadReadOnly;
  final String backLabel;

  @override
  State<TaskDetailScreen> createState() => _TaskDetailScreenState();
}

class _TaskDetailScreenState extends State<TaskDetailScreen> {
  late final int? _account;
  PersonalTask? _task;
  TaskStatus? _selected;
  ApiFailure? _error;
  bool _loading = true;
  bool _saving = false;
  bool _saved = false;

  @override
  void initState() {
    super.initState();
    _account = widget.session.profile?.id;
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
      _saved = false;
    });
    try {
      final task =
          await (widget.loadReadOnly?.call() ??
              widget.api.detail(widget.taskId));
      if (!mounted) return;
      setState(() {
        _task = task;
        _selected = task.status;
        _loading = false;
      });
    } on ApiFailure catch (error) {
      if (!mounted) return;
      setState(() {
        _task = null;
        _error = error;
        _loading = false;
      });
      if (error.status == 401) widget.session.expire();
    }
  }

  Future<void> _save() async {
    final task = _task;
    final status = _selected;
    if (_saving ||
        _loading ||
        widget.loadReadOnly != null ||
        task == null ||
        !task.canUpdate ||
        task.status == null ||
        status == null ||
        status == task.status) {
      return;
    }

    setState(() {
      _saving = true;
      _saved = false;
      _error = null;
    });
    try {
      final result = await widget.api.update(task.id, status);
      if (!mounted) return;
      setState(() {
        _task = result;
        _selected = result.status;
        _saving = false;
        _saved = true;
      });
    } on ApiFailure catch (error) {
      if (!mounted) return;
      setState(() {
        _saving = false;
        _error = error;
        if (error.status == 403 || error.status == 404) {
          _task = null;
        }
      });
      if (error.status == 401) widget.session.expire();
    }
  }

  @override
  Widget build(BuildContext context) => PopScope(
    canPop: !_saving,
    child: Scaffold(
      body: SafeArea(
        child: ListenableBuilder(
          listenable: widget.session,
          builder: (context, child) => ListView(
            padding: const EdgeInsets.fromLTRB(14, 12, 14, 28),
            children: [
              Align(
                alignment: Alignment.centerLeft,
                child: TextButton.icon(
                  onPressed: _saving ? null : () => Navigator.of(context).pop(),
                  icon: const Icon(Icons.arrow_back, size: 17),
                  label: Text(widget.backLabel),
                ),
              ),
              const SizedBox(height: 8),
              if (widget.session.phase == SessionPhase.checking)
                const LinearProgressIndicator(semanticsLabel: 'Memeriksa sesi')
              else if (widget.session.phase != SessionPhase.signedIn ||
                  widget.session.profile?.id != _account)
                InlineNotice(
                  widget.session.message ??
                      'Silakan masuk kembali untuk membuka tugas.',
                )
              else if (_loading)
                const LinearProgressIndicator(semanticsLabel: 'Memuat tugas')
              else if (_task case final task?)
                _content(task)
              else if (_error case final error?) ...[
                InlineNotice(error.message),
                if (error.status != 401 &&
                    error.status != 403 &&
                    error.status != 404)
                  Align(
                    alignment: Alignment.centerLeft,
                    child: OutlinedButton(
                      onPressed: _load,
                      child: const Text('Coba lagi'),
                    ),
                  ),
              ],
            ],
          ),
        ),
      ),
    ),
  );

  Widget _content(PersonalTask task) {
    final assignee = widget.loadReadOnly == null
        ? widget.session.profile?.name
        : task.assigneeName ?? 'Belum ditetapkan';
    final source = task.sourceEventTitle;
    final decision = task.decisionSummary;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(displayName(task.title), style: EvaloopTheme.heading),
        const SizedBox(height: 10),
        Align(
          alignment: Alignment.centerLeft,
          child: TaskStatusPill(task.status),
        ),
        const SizedBox(height: 16),
        const Divider(height: 1, color: EvaloopTheme.border),
        const SizedBox(height: 14),
        TaskField('Event', Text(displayName(task.event.title))),
        const SizedBox(height: 14),
        TaskField('Tenggat', TaskDeadline(task)),
        if (assignee != null) ...[
          const SizedBox(height: 14),
          TaskField('PIC', Text(displayName(assignee))),
        ],
        if (decision != null && decision.trim().isNotEmpty) ...[
          const SizedBox(height: 14),
          TaskField('Keputusan rapat', Text(displayName(decision))),
        ],
        if (source != null) ...[
          const SizedBox(height: 14),
          TaskField('Sumber legacy', Text(displayName(source))),
        ],
        const SizedBox(height: 20),
        if (_error case final error?) ...[
          InlineNotice(error.message),
          const SizedBox(height: 12),
        ],
        if (_saved) ...[
          Semantics(
            liveRegion: true,
            child: const Text(
              'Progres tugas diperbarui.',
              style: TextStyle(
                color: Color(0xFF167347),
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          const SizedBox(height: 12),
        ],
        if (widget.loadReadOnly == null &&
            task.canUpdate &&
            task.status != null)
          _editor(task),
      ],
    );
  }

  Widget _editor(PersonalTask task) => DecoratedBox(
    decoration: BoxDecoration(
      color: Colors.white,
      border: Border.all(color: EvaloopTheme.border),
      borderRadius: BorderRadius.circular(10),
    ),
    child: Padding(
      padding: const EdgeInsets.all(13),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Status tugas',
            style: TextStyle(fontWeight: FontWeight.w600),
          ),
          DropdownButton<TaskStatus>(
            isExpanded: true,
            value: _selected,
            items: [
              for (final status in TaskStatus.values)
                DropdownMenuItem(
                  value: status,
                  child: Text(
                    status.label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
            ],
            onChanged: _saving
                ? null
                : (value) => setState(() {
                    _selected = value;
                    _saved = false;
                    _error = null;
                  }),
          ),
          const SizedBox(height: 4),
          const Text(
            'Perubahan disimpan setelah konfirmasi.',
            style: TextStyle(color: EvaloopTheme.muted, fontSize: 12),
          ),
          const SizedBox(height: 10),
          OutlinedButton(
            onPressed: _saving || _selected == null || _selected == task.status
                ? null
                : _save,
            style: OutlinedButton.styleFrom(minimumSize: const Size(132, 44)),
            child: Text(_saving ? 'Menyimpan…' : 'Simpan status'),
          ),
        ],
      ),
    ),
  );
}
