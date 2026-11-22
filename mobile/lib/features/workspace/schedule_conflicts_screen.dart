import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../core/evaloop_time.dart';
import '../../shared/api_result.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_session.dart';
import 'collision_report.dart';
import 'workspace_status.dart';
import 'workspace_api.dart';

const _blue = Color(0xFF286F91);
const _blueSoft = Color(0xFFEAF4F9);
const _amber = Color(0xFF986000);
const _amberSoft = Color(0xFFFFF4D8);
const _section = TextStyle(
  fontFamily: 'Geist',
  fontSize: 18,
  fontWeight: FontWeight.w600,
);

class ScheduleConflictsScreen extends StatefulWidget {
  const ScheduleConflictsScreen({
    super.key,
    required this.eventId,
    required this.api,
    required this.session,
    required this.onPublicEvent,
  });

  final int eventId;
  final WorkspaceApi api;
  final AuthSession session;
  final void Function(BuildContext, int) onPublicEvent;

  @override
  State<ScheduleConflictsScreen> createState() =>
      _ScheduleConflictsScreenState();
}

class _ScheduleConflictsScreenState extends State<ScheduleConflictsScreen> {
  late final int? _account;
  late Future<CollisionReport> _future;
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _account = widget.session.profile?.id;
    _future = _fetch();
  }

  Future<CollisionReport> _fetch() => readWithSession(
    widget.session,
    widget.api.conflicts(widget.eventId, _page),
  );

  void _load(int page) => setState(() {
    _page = page;
    _future = _fetch();
  });

  Future<void> _refresh() async {
    _load(_page);
    try {
      await _future;
    } on Exception {
      // ApiResult displays the error and retry action.
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: ListenableBuilder(
        listenable: widget.session,
        builder: (context, _) {
          final session = widget.session;
          return RefreshIndicator(
            onRefresh: _refresh,
            notificationPredicate: (notification) =>
                notification.depth == 0 &&
                session.phase == SessionPhase.signedIn &&
                session.profile?.id == _account,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(14, 12, 14, 26),
              children: [
                Align(
                  alignment: Alignment.centerLeft,
                  child: TextButton.icon(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.arrow_back, size: 16),
                    style: TextButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                    ),
                    label: const Text('Kembali ke workspace'),
                  ),
                ),
                const SizedBox(height: 8),
                const Text('Jadwal & bentrokan', style: EvaloopTheme.heading),
                const SizedBox(height: 14),
                if (session.phase == SessionPhase.checking)
                  const LinearProgressIndicator(
                    semanticsLabel: 'Memeriksa sesi',
                  )
                else if (session.phase != SessionPhase.signedIn ||
                    session.profile?.id != _account)
                  InlineNotice(
                    session.message ??
                        'Silakan masuk kembali untuk memeriksa jadwal.',
                  )
                else
                  ApiResult(
                    future: _future,
                    onRetry: () => _load(_page),
                    content: _report,
                  ),
              ],
            ),
          );
        },
      ),
    ),
  );

  Widget _report(CollisionReport report) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Wrap(
        spacing: 10,
        runSpacing: 6,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          Text(
            displayName(report.event.title),
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
          WorkspaceStatus(report.event.status),
        ],
      ),
      const SizedBox(height: 12),
      Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: EvaloopTheme.ice,
          border: Border.all(color: EvaloopTheme.border),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _field('Jadwal kegiatan', _timeRange(report.event), emphasis: true),
            const SizedBox(height: 10),
            _field('Lokasi', _venue(report.event)),
            const SizedBox(height: 10),
            _field(
              'Target program studi',
              report.targets.isEmpty
                  ? 'Belum ditentukan'
                  : report.targets.map(displayName).join(', '),
            ),
          ],
        ),
      ),
      const SizedBox(height: 24),
      ..._results(report),
    ],
  );

  List<Widget> _results(CollisionReport report) {
    if (report.unavailableReason case final reason?) {
      return [
        const Text('Jadwal belum diperiksa', style: _section),
        const SizedBox(height: 8),
        DecoratedBox(
          decoration: const BoxDecoration(
            border: Border(left: BorderSide(color: _amber, width: 2)),
          ),
          child: Padding(
            padding: const EdgeInsets.only(left: 12),
            child: Text(reason),
          ),
        ),
      ];
    }
    if (report.total == 0) {
      return const [
        Text('Tidak ada potensi bentrokan', style: _section),
        SizedBox(height: 8),
        Text(
          'Tidak ada jadwal yang beririsan dalam kegiatan publik yang diperiksa.',
          style: TextStyle(color: EvaloopTheme.muted),
        ),
      ];
    }
    return [
      Text('${report.total} jadwal beririsan', style: _section),
      const SizedBox(height: 8),
      const Text(
        'Irisan waktu memberi konteks jadwal. Lokasi atau target yang sama perlu ditinjau lebih lanjut.',
        style: TextStyle(color: EvaloopTheme.muted),
      ),
      const SizedBox(height: 14),
      if (report.warnings.isEmpty)
        Align(
          alignment: Alignment.centerLeft,
          child: TextButton(
            onPressed: () => _load(1),
            child: const Text('Buka halaman pertama'),
          ),
        )
      else
        DecoratedBox(
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border.all(color: EvaloopTheme.border),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              for (final (index, warning) in report.warnings.indexed) ...[
                if (index > 0)
                  const Divider(height: 1, color: EvaloopTheme.border),
                _warning(warning),
              ],
            ],
          ),
        ),
      if (report.lastPage > 1)
        Wrap(
          alignment: WrapAlignment.spaceBetween,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            TextButton(
              onPressed: report.page > 1 ? () => _load(report.page - 1) : null,
              child: const Text('Sebelumnya'),
            ),
            Text('${report.page} / ${report.lastPage}'),
            TextButton(
              onPressed: report.page < report.lastPage
                  ? () => _load(report.page + 1)
                  : null,
              child: const Text('Berikutnya'),
            ),
          ],
        ),
    ];
  }

  Widget _warning(CollisionWarning warning) {
    final title = displayName(warning.event.title);
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontFamily: 'Geist',
              fontSize: 16,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(_timeRange(warning.event), style: const TextStyle(color: _blue)),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 4,
            alignment: WrapAlignment.spaceBetween,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: [
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: [
                  for (final reason in warning.reasons) _badge(reason),
                ],
              ),
              TextButton.icon(
                onPressed: () =>
                    widget.onPublicEvent(context, warning.event.id),
                label: Text(
                  'Lihat detail',
                  semanticsLabel: 'Lihat detail publik $title',
                ),
                icon: const Icon(Icons.arrow_forward, size: 14),
                iconAlignment: IconAlignment.end,
                style: TextButton.styleFrom(
                  padding: const EdgeInsets.symmetric(horizontal: 6),
                  minimumSize: const Size(0, 44),
                ),
              ),
            ],
          ),
          ExpansionTile(
            title: const Text(
              'Alasan bentrokan',
              style: TextStyle(fontSize: 13),
            ),
            tilePadding: EdgeInsets.zero,
            shape: const Border(),
            collapsedShape: const Border(),
            childrenPadding: const EdgeInsets.only(bottom: 8),
            expandedCrossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _field('Lokasi kegiatan pembanding', _venue(warning.event)),
              const SizedBox(height: 8),
              for (final reason in warning.reasons)
                Padding(
                  padding: const EdgeInsets.only(bottom: 6),
                  child: Text(reason.description),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _badge(CollisionReason reason) {
    final time = reason.type == 'time_overlap';
    return DecoratedBox(
      decoration: BoxDecoration(
        color: time ? _blueSoft : _amberSoft,
        borderRadius: BorderRadius.circular(22),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        child: Text(
          reason.label,
          style: TextStyle(
            color: time ? _blue : _amber,
            fontSize: 12,
            fontWeight: FontWeight.w500,
          ),
        ),
      ),
    );
  }

  Widget _field(String label, String value, {bool emphasis = false}) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        label,
        style: const TextStyle(fontSize: 12, color: EvaloopTheme.muted),
      ),
      const SizedBox(height: 3),
      Text(
        value,
        style: emphasis
            ? const TextStyle(color: _blue, fontWeight: FontWeight.w600)
            : null,
      ),
    ],
  );

  String _venue(CollisionEvent event) {
    final value = event.venue?.trim();
    return value == null || value.isEmpty
        ? 'Belum ditentukan'
        : displayName(value);
  }

  String _timeRange(CollisionEvent event) {
    final start = event.startsAt == null
        ? null
        : EvaloopTime.wita(event.startsAt!);
    final end = event.endsAt == null ? null : EvaloopTime.wita(event.endsAt!);
    if (start == null && end == null) return 'Belum ditentukan';
    String format(DateTime date) =>
        '${EvaloopTime.shortDate(date)} · ${EvaloopTime.clock(date)}';
    final from = start == null ? 'Awal belum ditentukan' : format(start);
    final until = end == null
        ? 'Akhir belum ditentukan'
        : start != null && EvaloopTime.sameDay(start, end)
        ? EvaloopTime.clock(end)
        : format(end);
    return '$from – $until WITA';
  }
}
