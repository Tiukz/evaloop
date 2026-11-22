import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../core/evaloop_time.dart';
import '../../shared/api_result.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_session.dart';
import '../tasks/tasks_api.dart';
import '../workspace/workspace_api.dart';
import 'meeting_detail_screen.dart';
import 'meeting_models.dart';
import 'meeting_page.dart';

class MeetingHubScreen extends StatefulWidget {
  const MeetingHubScreen({
    super.key,
    required this.eventId,
    required this.api,
    required this.tasks,
    required this.session,
  });

  final int eventId;
  final WorkspaceApi api;
  final TasksApi tasks;
  final AuthSession session;

  @override
  State<MeetingHubScreen> createState() => _MeetingHubScreenState();
}

class _MeetingHubScreenState extends State<MeetingHubScreen> {
  final _search = TextEditingController();
  late final int? _account;
  late Future<MeetingAgenda> _agenda;
  String _query = '';
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _account = widget.session.profile?.id;
    _agenda = _fetch();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<MeetingAgenda> _fetch() => readWithSession(
    widget.session,
    widget.api.meetings(widget.eventId, _page, _query),
  );

  Future<void> _load([int? page]) async {
    setState(() {
      _page = page ?? _page;
      _agenda = _fetch();
    });
    try {
      await _agenda;
    } on Exception {
      // ApiResult shows the error and retry.
    }
  }

  void _submit() {
    FocusScope.of(context).unfocus();
    _query = _search.text.trim();
    _load(1);
  }

  void _open(MeetingItem item) => Navigator.of(context).push<void>(
    MaterialPageRoute(
      builder: (_) => MeetingDetailScreen(
        eventId: widget.eventId,
        meetingId: item.id,
        api: widget.api,
        tasks: widget.tasks,
        session: widget.session,
      ),
    ),
  );

  @override
  Widget build(BuildContext context) => MeetingPage(
    session: widget.session,
    accountId: _account,
    backLabel: 'Kembali ke workspace',
    future: _agenda,
    reload: _load,
    content: _content,
  );

  Widget _content(MeetingAgenda agenda) {
    final upcoming =
        agenda.items
            .where((item) => !item.scheduledAt.isBefore(agenda.referenceTime))
            .toList()
          ..sort((a, b) => a.scheduledAt.compareTo(b.scheduledAt));
    final past = agenda.items
        .where((item) => item.scheduledAt.isBefore(agenda.referenceTime))
        .toList();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        MeetingEventContext(agenda.event),
        const SizedBox(height: 22),
        const Text('Meeting Hub', style: EvaloopTheme.heading),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _search,
                maxLength: 120,
                textInputAction: TextInputAction.search,
                onSubmitted: (_) => _submit(),
                decoration: const InputDecoration(
                  hintText: 'Cari judul atau catatan rapat',
                  counterText: '',
                  prefixIcon: Icon(Icons.search, size: 18),
                ),
              ),
            ),
            const SizedBox(width: 8),
            OutlinedButton(
              onPressed: _submit,
              style: OutlinedButton.styleFrom(
                minimumSize: const Size(90, 44),
                foregroundColor: EvaloopTheme.ink,
                backgroundColor: Colors.white,
                side: const BorderSide(color: EvaloopTheme.formBorder),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              child: const Text('Cari'),
            ),
          ],
        ),
        const SizedBox(height: 18),
        if (agenda.items.isEmpty)
          Material(
            color: Colors.white,
            shape: meetingBorder(),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    agenda.total > 0
                        ? 'Halaman ini tidak berisi rapat'
                        : _query.isNotEmpty
                        ? 'Tidak ada rapat yang cocok.'
                        : 'Belum ada rapat',
                    style: meetingHeading,
                  ),
                  if (agenda.total > 0)
                    TextButton(
                      onPressed: () => _load(1),
                      child: const Text('Buka halaman pertama'),
                    )
                  else if (_query.isEmpty)
                    const Text(
                      'Rapat dan catatannya akan tampil di sini.',
                      style: TextStyle(color: EvaloopTheme.muted),
                    ),
                ],
              ),
            ),
          ),
        if (upcoming.isNotEmpty) _group('Mendatang', upcoming),
        if (upcoming.isNotEmpty && past.isNotEmpty) const SizedBox(height: 20),
        if (past.isNotEmpty) _group('Jadwal sebelumnya', past),
        if (agenda.lastPage > 1)
          Wrap(
            alignment: WrapAlignment.spaceBetween,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: [
              TextButton(
                onPressed: agenda.page > 1
                    ? () => _load(agenda.page - 1)
                    : null,
                child: const Text('Sebelumnya'),
              ),
              Text('${agenda.page} / ${agenda.lastPage}'),
              TextButton(
                onPressed: agenda.page < agenda.lastPage
                    ? () => _load(agenda.page + 1)
                    : null,
                child: const Text('Berikutnya'),
              ),
            ],
          ),
      ],
    );
  }

  Widget _group(String heading, List<MeetingItem> items) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(heading, style: meetingHeading),
      const SizedBox(height: 8),
      Material(
        color: Colors.white,
        shape: meetingBorder(),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            for (final (index, item) in items.indexed) ...[
              if (index > 0)
                const Divider(height: 1, color: EvaloopTheme.border),
              _item(item),
            ],
          ],
        ),
      ),
    ],
  );

  Widget _item(MeetingItem item) {
    final scheduled = EvaloopTime.wita(item.scheduledAt);
    final notes = displayName(item.notesExcerpt);
    return InkWell(
      onTap: () => _open(item),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(displayName(item.title), style: meetingHeading),
            const SizedBox(height: 4),
            Wrap(
              spacing: 10,
              runSpacing: 4,
              children: [
                Text(
                  '${EvaloopTime.calendarDate(scheduled)} · ${EvaloopTime.clock(scheduled).replaceFirst(':', '.')} WITA',
                  style: const TextStyle(color: EvaloopTheme.muted),
                ),
                if (item.decisionsCount > 0)
                  Text(
                    '${item.decisionsCount} keputusan',
                    style: const TextStyle(color: EvaloopTheme.muted),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              notes.isEmpty ? 'Belum ada catatan.' : notes,
              style: TextStyle(
                fontSize: 15,
                height: 1.6,
                color: notes.isEmpty ? EvaloopTheme.muted : EvaloopTheme.ink,
              ),
            ),
            const SizedBox(height: 10),
            const Wrap(
              spacing: 8,
              children: [
                Text(
                  'Buka catatan',
                  style: TextStyle(
                    color: EvaloopTheme.teal,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Icon(Icons.arrow_forward, size: 16, color: EvaloopTheme.teal),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
