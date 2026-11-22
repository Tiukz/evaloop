import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_api.dart';
import '../auth/auth_session.dart';
import 'task_detail_screen.dart';
import 'task_widgets.dart';
import 'tasks_api.dart';

class TasksScreen extends StatefulWidget {
  const TasksScreen({
    super.key,
    required this.api,
    required this.session,
    this.workspaceEventId,
  });

  final TasksApi api;
  final AuthSession session;
  final int? workspaceEventId;

  @override
  State<TasksScreen> createState() => _TasksScreenState();
}

class _TasksScreenState extends State<TasksScreen> {
  final _search = TextEditingController();
  final _scroll = ScrollController();
  TaskView _view = TaskView.all;
  TaskSort _sort = TaskSort.nearest;
  TaskStatus? _status;
  int _eventId = 0;
  final Map<String, String> _filters = {};

  TaskPage? _result;
  ApiFailure? _error;
  bool _loading = true;
  int _request = 0;
  int _requestedPage = 1;

  @override
  void initState() {
    super.initState();
    _captureFilters();
    _load();
  }

  @override
  void dispose() {
    _search.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _captureFilters() {
    final keyword = _search.text.trim();
    _filters
      ..clear()
      ..addAll({
        'view': _view.name,
        'sort': _sort.value,
        if (keyword.isNotEmpty) 'q': keyword,
        if (_status case final status?) 'status': status.value,
        if ((widget.workspaceEventId ?? _eventId) != 0)
          'event': '${widget.workspaceEventId ?? _eventId}',
      });
  }

  void _apply() {
    FocusScope.of(context).unfocus();
    _captureFilters();
    _load();
  }

  void _changeView(TaskView view) {
    if (view == _view) return;
    _view = view;
    // Keep applied search, event and sort as on the web view tabs.
    _search.text = _filters['q'] ?? '';
    _eventId = int.tryParse(_filters['event'] ?? '') ?? 0;
    _sort = _filters['sort'] == TaskSort.farthest.value
        ? TaskSort.farthest
        : TaskSort.nearest;
    _status = null;
    _apply();
  }

  void _reset() {
    _search.clear();
    _status = null;
    _eventId = 0;
    _sort = _filters['sort'] == TaskSort.farthest.value
        ? TaskSort.farthest
        : TaskSort.nearest;
    _apply();
  }

  Future<void> _load({
    int page = 1,
    bool scrollToTop = false,
    bool keepRows = false,
  }) async {
    final request = ++_request;
    final query = {..._filters, 'page': '$page'};
    if (scrollToTop && _scroll.hasClients) _scroll.jumpTo(0);
    setState(() {
      _requestedPage = page;
      _loading = !keepRows || _result == null;
      _error = null;
    });

    try {
      final result = await widget.api.list(query);
      if (!mounted || request != _request) return;
      if (result.page > result.lastPage) {
        await _load(page: result.lastPage, scrollToTop: true);
        return;
      }
      setState(() {
        _result = result;
        // An event may have disappeared after assignment or membership changed.
        if (!result.events.any((event) => event.id == _eventId)) _eventId = 0;
        _loading = false;
      });
    } on ApiFailure catch (error) {
      if (!mounted || request != _request) return;
      setState(() {
        _loading = false;
        _error = error;
        if (error.status == 401 || error.status == 403) _result = null;
      });
      if (error.status == 401) widget.session.expire();
    }
  }

  Future<void> _open(PersonalTask task) async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (context) => TaskDetailScreen(
          taskId: task.id,
          api: widget.api,
          session: widget.session,
        ),
      ),
    );
    if (mounted && widget.session.phase == SessionPhase.signedIn) {
      await _load(page: _requestedPage, keepRows: true);
    }
  }

  bool get _filtered =>
      _filters.containsKey('q') ||
      _filters.containsKey('status') ||
      (widget.workspaceEventId == null && _filters.containsKey('event'));

  @override
  Widget build(BuildContext context) => RefreshIndicator(
    onRefresh: () => _load(page: _requestedPage, keepRows: true),
    child: ListView(
      controller: _scroll,
      physics: const AlwaysScrollableScrollPhysics(),
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      padding: const EdgeInsets.fromLTRB(14, 16, 14, 24),
      children: [
        Text(
          widget.workspaceEventId == null
              ? 'Tugas Saya'
              : 'Tugas pribadi di event ini',
          style: EvaloopTheme.heading,
        ),
        const SizedBox(height: 12),
        _views(),
        const SizedBox(height: 13),
        _toolbar(),
        const SizedBox(height: 14),
        if (_loading)
          const LinearProgressIndicator(semanticsLabel: 'Memuat tugas')
        else if (_error case final error?) ...[
          InlineNotice(error.message),
          Align(
            alignment: Alignment.centerLeft,
            child: OutlinedButton(
              onPressed: () => _load(page: _requestedPage),
              child: const Text('Coba lagi'),
            ),
          ),
        ] else if (_result case final result?)
          ..._rows(result),
      ],
    ),
  );

  Widget _views() => DecoratedBox(
    decoration: const BoxDecoration(
      border: Border(bottom: BorderSide(color: EvaloopTheme.border)),
    ),
    child: Wrap(
      alignment: WrapAlignment.spaceBetween,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: [
        Wrap(
          children: [
            for (final view in TaskView.values)
              Semantics(
                selected: _view == view,
                child: DecoratedBox(
                  position: DecorationPosition.foreground,
                  decoration: BoxDecoration(
                    border: Border(
                      bottom: BorderSide(
                        color: _view == view
                            ? EvaloopTheme.teal
                            : Colors.transparent,
                        width: 2,
                      ),
                    ),
                  ),
                  child: TextButton(
                    onPressed: () => _changeView(view),
                    style: TextButton.styleFrom(
                      foregroundColor: _view == view
                          ? EvaloopTheme.teal
                          : EvaloopTheme.muted,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 6,
                      ),
                      minimumSize: const Size(0, 44),
                      shape: const RoundedRectangleBorder(),
                    ),
                    child: Text(
                      view.label,
                      style: TextStyle(
                        fontWeight: _view == view
                            ? FontWeight.w600
                            : FontWeight.w500,
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
        if (_result case final result? when !_loading && _error == null)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Semantics(
              liveRegion: true,
              child: Text(
                '${result.total} tugas',
                style: const TextStyle(color: EvaloopTheme.muted, fontSize: 12),
              ),
            ),
          ),
      ],
    ),
  );

  Widget _toolbar() {
    final events = _result?.events ?? const <TaskEvent>[];
    final search = TextField(
      controller: _search,
      maxLength: 120,
      textInputAction: TextInputAction.search,
      onSubmitted: (_) => _apply(),
      decoration: const InputDecoration(
        counterText: '',
        hintText: 'Cari tugas…',
        prefixIcon: Icon(Icons.search, size: 18),
        contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 12),
      ),
    );
    final fields = [
      search,
      _filter<String>(
        label: 'Status',
        value: _status?.value ?? '',
        options: [
          ('', 'Semua status'),
          for (final status in TaskStatus.values) (status.value, status.label),
        ],
        select: (value) => setState(() => _status = TaskStatus.parse(value)),
      ),
      if (widget.workspaceEventId == null)
        _filter<int>(
          label: 'Event',
          value: _eventId,
          options: [
            (0, 'Semua event'),
            for (final event in events) (event.id, displayName(event.title)),
          ],
          select: (value) => setState(() => _eventId = value),
        ),
      _filter<TaskSort>(
        label: 'Tenggat',
        value: _sort,
        options: [for (final sort in TaskSort.values) (sort, sort.label)],
        select: (value) => setState(() => _sort = value),
      ),
    ];

    return LayoutBuilder(
      builder: (context, constraints) {
        final split =
            constraints.maxWidth >= 286 &&
            MediaQuery.textScalerOf(context).scale(14) <= 18;
        final width = split
            ? (constraints.maxWidth - 10) / 2
            : constraints.maxWidth;
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Wrap(
              spacing: 10,
              runSpacing: 9,
              crossAxisAlignment: WrapCrossAlignment.end,
              children: [
                for (final field in fields)
                  SizedBox(width: width, child: field),
              ],
            ),
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                OutlinedButton(
                  onPressed: _apply,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: EvaloopTheme.ink,
                    backgroundColor: Colors.white,
                    minimumSize: const Size(140, 44),
                    side: const BorderSide(color: EvaloopTheme.formBorder),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  child: const Text('Terapkan'),
                ),
                if (_filtered)
                  TextButton(onPressed: _reset, child: const Text('Reset')),
              ],
            ),
          ],
        );
      },
    );
  }

  Widget _filter<T>({
    required String label,
    required T value,
    required List<(T, String)> options,
    required ValueChanged<T> select,
  }) => TaskField(
    label,
    Semantics(
      label: label,
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border.all(color: EvaloopTheme.border),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 9),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<T>(
              isExpanded: true,
              value: options.any((option) => option.$1 == value) ? value : null,
              icon: const Icon(Icons.expand_more, size: 17),
              items: [
                for (final (id, title) in options)
                  DropdownMenuItem<T>(
                    value: id,
                    child: Text(
                      title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
              ],
              onChanged: (value) {
                if (value != null) select(value);
              },
            ),
          ),
        ),
      ),
    ),
  );

  List<Widget> _rows(TaskPage result) {
    if (result.tasks.isEmpty) {
      return [
        const Divider(height: 1, color: EvaloopTheme.border),
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 18, horizontal: 9),
          child: Text(
            _filtered || _view != TaskView.all
                ? 'Tidak ada tugas yang cocok.'
                : 'Belum ada tugas untuk Anda.',
            style: Theme.of(context).textTheme.titleLarge,
          ),
        ),
      ];
    }

    return [
      const Divider(height: 1, color: EvaloopTheme.border),
      for (final task in result.tasks)
        TaskRow(task: task, onOpen: () => _open(task)),
      if (result.lastPage > 1)
        Wrap(
          alignment: WrapAlignment.spaceBetween,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            TextButton(
              onPressed: result.page > 1
                  ? () => _load(page: result.page - 1, scrollToTop: true)
                  : null,
              child: const Text('Sebelumnya'),
            ),
            Text(
              '${result.page} / ${result.lastPage}',
              semanticsLabel: 'Halaman ${result.page} dari ${result.lastPage}',
            ),
            TextButton(
              onPressed: result.page < result.lastPage
                  ? () => _load(page: result.page + 1, scrollToTop: true)
                  : null,
              child: const Text('Berikutnya'),
            ),
          ],
        ),
    ];
  }
}
