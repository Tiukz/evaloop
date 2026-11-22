import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../core/evaloop_time.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_api.dart';
import 'radar_api.dart';
import 'radar_detail_screen.dart';
import 'radar_event_widgets.dart';

class RadarScreen extends StatefulWidget {
  const RadarScreen({super.key, required this.api});
  final RadarApi api;

  @override
  State<RadarScreen> createState() => _RadarScreenState();
}

enum _Filter {
  faculty('Fakultas', 'faculty_id'),
  program('Program studi', 'study_program_id'),
  organization('Organisasi', 'organization_id'),
  date('Tanggal', 'date_from');

  const _Filter(this.label, this.parameter);
  final String label;
  final String parameter;
}

class _RadarScreenState extends State<RadarScreen> {
  final _search = TextEditingController();
  final _scroll = ScrollController();
  RadarPeriod _period = RadarPeriod.all;
  final Map<String, String> _query = {};
  RadarPage? _page;
  ApiFailure? _error;
  bool _loading = true;
  int _request = 0;
  int _requestedPage = 1;
  _Filter? _openFilter;
  int _option = 0;
  DateTime? _from;
  DateTime? _to;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _search.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _load({int page = 1, bool moveToTop = false}) async {
    final request = ++_request;
    final query = {..._query, 'period': _period.name, 'page': '$page'};
    setState(() {
      _requestedPage = page;
      _loading = true;
      _error = null;
    });
    if (moveToTop && _scroll.hasClients) _scroll.jumpTo(0);
    try {
      final result = await widget.api.list(query);
      if (!mounted || request != _request) return;
      setState(() {
        _page = result;
        _loading = false;
      });
    } on ApiFailure catch (error) {
      if (!mounted || request != _request) return;
      setState(() {
        _error = error;
        _loading = false;
      });
    }
  }

  void _apply() {
    final q = _search.text.trim();
    if (q.isEmpty) {
      _query.remove('q');
    } else {
      _query['q'] = q;
    }
    FocusScope.of(context).unfocus();
    if (_openFilter case final filter?) {
      if (filter == _Filter.date) {
        _setDate('date_from', _from);
        _setDate('date_to', _to);
      } else {
        _setOption(filter, _option);
      }
      _openFilter = null;
    }
    _load();
  }

  void _setOption(_Filter filter, int value) {
    if (value == 0) {
      _query.remove(filter.parameter);
    } else {
      _query[filter.parameter] = '$value';
    }
    if (filter == _Filter.faculty && value != 0) {
      final programId = _query[_Filter.program.parameter];
      final program = _page?.programs
          .where((option) => '${option.id}' == programId)
          .firstOrNull;
      if (program?.facultyId != value) _query.remove(_Filter.program.parameter);
    }
  }

  void _setDate(String key, DateTime? value) {
    if (value == null) {
      _query.remove(key);
    } else {
      _query[key] = EvaloopTime.parameter(value);
    }
  }

  void _reset({bool all = false}) {
    _query.clear();
    _search.clear();
    _openFilter = null;
    if (all) _period = RadarPeriod.all;
    _load();
  }

  void _open(PublicEvent event) => Navigator.of(context).push(
    MaterialPageRoute<void>(
      builder: (_) => RadarDetailScreen(
        api: widget.api,
        eventId: event.id,
        reference: _page!.reference,
      ),
    ),
  );

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () => _load(page: _requestedPage),
      child: ListView(
        controller: _scroll,
        physics: const AlwaysScrollableScrollPhysics(),
        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 24),
        children: [
          _hero(),
          const SizedBox(height: 12),
          _toolbar(),
          const SizedBox(height: 16),
          if (_loading)
            const LinearProgressIndicator(semanticsLabel: 'Memuat kegiatan')
          else if (_error case final error?)
            RadarFailure(error, onRetry: () => _load(page: _requestedPage))
          else if (_page case final result?)
            ..._results(result),
        ],
      ),
    );
  }

  Widget _hero() => Container(
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      gradient: EvaloopTheme.editorial,
      border: Border.all(color: EvaloopTheme.border),
      borderRadius: BorderRadius.circular(20),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Text('Ada apa di kampus?', style: EvaloopTheme.heading),
        const SizedBox(height: 14),
        Container(
          padding: const EdgeInsets.all(4),
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border.all(color: EvaloopTheme.border),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _search,
                  textInputAction: TextInputAction.search,
                  maxLength: 120,
                  onSubmitted: (_) => _apply(),
                  decoration: const InputDecoration(
                    hintText: 'Cari kegiatan…',
                    counterText: '',
                    prefixIcon: Icon(Icons.search, size: 22),
                    prefixIconConstraints: BoxConstraints(minWidth: 38),
                    contentPadding: EdgeInsets.symmetric(vertical: 12),
                    border: InputBorder.none,
                    enabledBorder: InputBorder.none,
                    focusedBorder: InputBorder.none,
                  ),
                ),
              ),
              FilledButton(
                onPressed: _apply,
                style: FilledButton.styleFrom(
                  minimumSize: const Size(90, 44),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 8,
                  ),
                ),
                child: const Text('Cari'),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        _featured(),
      ],
    ),
  );

  Widget _featured() {
    final event = _loading || _error != null ? null : _page?.featured;
    Widget content;
    if (_loading || _error != null) {
      content = Text(
        _loading
            ? 'Memuat sorotan kegiatan…'
            : 'Sorotan kegiatan belum tersedia.',
      );
    } else if (event == null) {
      content = const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Event mendatang',
            style: TextStyle(
              color: EvaloopTheme.teal,
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
          SizedBox(height: 8),
          Text('Belum ada sorotan mendatang.', style: radarHeading),
          Text('Jelajahi hasil di bawah atau sesuaikan filter.'),
        ],
      );
    } else {
      final start = event.start;
      content = Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Segera di kampus',
            style: TextStyle(
              color: EvaloopTheme.teal,
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 12),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
                decoration: BoxDecoration(
                  color: EvaloopTheme.ice,
                  borderRadius: BorderRadius.circular(11),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      EvaloopTime.pad(start.day),
                      style: EvaloopTheme.heading.copyWith(
                        color: EvaloopTheme.teal,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      '${EvaloopTime.month(start)}\n${start.year}',
                      style: const TextStyle(fontSize: 12, height: 1.35),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(displayName(event.title), style: radarHeading),
                    const SizedBox(height: 4),
                    Text('${EvaloopTime.clock(start)} WITA'),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              Expanded(
                child: Text(
                  displayName(event.organizer),
                  style: const TextStyle(fontSize: 12),
                ),
              ),
              const SizedBox(width: 4),
              TextButton(
                onPressed: () => _open(event),
                child: const Text('Lihat detail'),
              ),
            ],
          ),
        ],
      );
    }
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [EvaloopTheme.aqua, EvaloopTheme.ice],
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: content,
    );
  }

  Widget _toolbar() {
    final result = _page;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: EvaloopTheme.border),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(3),
            decoration: BoxDecoration(
              color: EvaloopTheme.ice,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Wrap(
              spacing: 2,
              runSpacing: 4,
              children: [
                for (final period in RadarPeriod.values)
                  Semantics(
                    selected: _period == period,
                    child: TextButton(
                      onPressed: () {
                        if (period == _period) return;
                        _period = period;
                        _openFilter = null;
                        _load();
                      },
                      style: TextButton.styleFrom(
                        minimumSize: const Size(0, 44),
                        padding: const EdgeInsets.symmetric(horizontal: 11),
                        foregroundColor: _period == period
                            ? Colors.white
                            : EvaloopTheme.ink,
                        backgroundColor: _period == period
                            ? EvaloopTheme.teal
                            : null,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(6),
                        ),
                      ),
                      child: Text(period.label),
                    ),
                  ),
              ],
            ),
          ),
          if (!_loading && _error == null && result != null) ...[
            const SizedBox(height: 5),
            Semantics(
              liveRegion: true,
              child: Text(
                '${result.total} kegiatan',
                style: const TextStyle(fontSize: 12, color: EvaloopTheme.muted),
              ),
            ),
          ],
          const SizedBox(height: 8),
          Wrap(
            spacing: 7,
            runSpacing: 6,
            children: [
              for (final filter in _Filter.values)
                OutlinedButton(
                  onPressed: result != null || filter == _Filter.date
                      ? () => _toggleFilter(filter)
                      : null,
                  style: OutlinedButton.styleFrom(
                    minimumSize: const Size(0, 44),
                    foregroundColor: _hasFilter(filter)
                        ? EvaloopTheme.teal
                        : EvaloopTheme.ink,
                    backgroundColor: _hasFilter(filter) || _openFilter == filter
                        ? EvaloopTheme.tealSoft
                        : null,
                    side: const BorderSide(color: EvaloopTheme.border),
                    padding: const EdgeInsets.symmetric(horizontal: 10),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Flexible(
                        child: Text(
                          _filterLabel(filter, result),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontSize: 12),
                        ),
                      ),
                      const SizedBox(width: 4),
                      Icon(
                        _openFilter == filter
                            ? Icons.expand_less
                            : Icons.expand_more,
                        size: 15,
                      ),
                    ],
                  ),
                ),
              if (_query.isNotEmpty)
                TextButton(
                  onPressed: _reset,
                  child: const Text('Reset filter'),
                ),
            ],
          ),
          if (_openFilter case final filter?) ...[
            const SizedBox(height: 12),
            _filterPanel(filter, result),
          ],
        ],
      ),
    );
  }

  bool _hasFilter(_Filter filter) =>
      _query.containsKey(filter.parameter) ||
      (filter == _Filter.date && _query.containsKey('date_to'));

  String _filterLabel(_Filter filter, RadarPage? result) {
    if (!_hasFilter(filter)) return filter.label;
    if (filter == _Filter.date) {
      final from = DateTime.tryParse(_query['date_from'] ?? '');
      final to = DateTime.tryParse(_query['date_to'] ?? '');
      final first = from == null ? 'Awal' : EvaloopTime.shortDate(from);
      final last = to == null ? 'Akhir' : EvaloopTime.shortDate(to);
      return '$first – $last';
    }
    final options = switch (filter) {
      _Filter.faculty => result?.faculties,
      _Filter.program => result?.programs,
      _Filter.organization => result?.organizations,
      _Filter.date => null,
    };
    final id = int.tryParse(_query[filter.parameter] ?? '');
    final option = options?.where((option) => option.id == id).firstOrNull;
    return option == null ? filter.label : displayName(option.name);
  }

  void _toggleFilter(_Filter filter) => setState(() {
    if (_openFilter == filter) {
      _openFilter = null;
      return;
    }
    _openFilter = filter;
    _option = int.tryParse(_query[filter.parameter] ?? '') ?? 0;
    _from = DateTime.tryParse(_query['date_from'] ?? '');
    _to = DateTime.tryParse(_query['date_to'] ?? '');
  });

  Widget _filterPanel(_Filter filter, RadarPage? result) {
    final faculty = int.tryParse(_query[_Filter.faculty.parameter] ?? '');
    final options = switch (filter) {
      _Filter.faculty => result?.faculties ?? const <RadarOption>[],
      _Filter.program =>
        result?.programs
                .where(
                  (program) => faculty == null || program.facultyId == faculty,
                )
                .toList() ??
            const <RadarOption>[],
      _Filter.organization => result?.organizations ?? const <RadarOption>[],
      _Filter.date => const <RadarOption>[],
    };
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: EvaloopTheme.ice,
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            filter == _Filter.date ? 'Rentang tanggal (WITA)' : filter.label,
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
          if (filter == _Filter.date)
            Wrap(
              spacing: 8,
              runSpacing: 4,
              children: [
                OutlinedButton(
                  onPressed: () => _pickDate(from: true),
                  child: Text(
                    _from == null
                        ? 'Tanggal awal'
                        : EvaloopTime.shortDate(_from!),
                  ),
                ),
                OutlinedButton(
                  onPressed: () => _pickDate(from: false),
                  child: Text(
                    _to == null ? 'Tanggal akhir' : EvaloopTime.shortDate(_to!),
                  ),
                ),
              ],
            )
          else
            DropdownButton<int>(
              isExpanded: true,
              value: options.any((option) => option.id == _option)
                  ? _option
                  : 0,
              items: [
                DropdownMenuItem(
                  value: 0,
                  child: Text('Semua ${filter.label.toLowerCase()}'),
                ),
                for (final option in options)
                  DropdownMenuItem(
                    value: option.id,
                    child: Text(
                      filter == _Filter.program
                          ? _programLabel(option, result)
                          : displayName(option.name),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
              ],
              onChanged: (value) => setState(() => _option = value ?? 0),
            ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              FilledButton(onPressed: _apply, child: const Text('Terapkan')),
              TextButton(
                onPressed: () {
                  _query.remove(filter.parameter);
                  if (filter == _Filter.date) _query.remove('date_to');
                  _openFilter = null;
                  _load();
                },
                child: const Text('Hapus filter'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  String _programLabel(RadarOption option, RadarPage? result) {
    final faculty = result?.faculties
        .where((item) => item.id == option.facultyId)
        .firstOrNull;
    return faculty == null
        ? displayName(option.name)
        : '${displayName(option.name)} · ${displayName(faculty.name)}';
  }

  Future<void> _pickDate({required bool from}) async {
    final now = EvaloopTime.wita(DateTime.now());
    final first = from ? DateTime(1) : _from ?? DateTime(1);
    final last = from ? _to ?? DateTime(9999, 12, 31) : DateTime(9999, 12, 31);
    final candidate =
        (from ? _from ?? _to : _to ?? _from) ??
        DateTime(now.year, now.month, now.day);
    final selected = await showDatePicker(
      context: context,
      firstDate: first,
      lastDate: last,
      initialDate: candidate.isBefore(first)
          ? first
          : candidate.isAfter(last)
          ? last
          : candidate,
    );
    if (!mounted || selected == null) return;
    setState(() {
      if (from) {
        _from = selected;
      } else {
        _to = selected;
      }
    });
  }

  List<Widget> _results(RadarPage result) {
    if (result.events.isEmpty) {
      if (result.total > 0) {
        return [
          const Text('Halaman hasil tidak tersedia', style: radarHeading),
          TextButton(
            onPressed: _load,
            child: const Text('Kembali ke halaman pertama'),
          ),
        ];
      }
      final title = _query.isNotEmpty
          ? 'Tidak ada kegiatan sesuai filter'
          : switch (_period) {
              RadarPeriod.all => 'Belum ada event publik',
              RadarPeriod.upcoming => 'Belum ada kegiatan mendatang',
              RadarPeriod.history => 'Belum ada riwayat kegiatan',
            };
      return [
        Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border.all(color: EvaloopTheme.border),
            borderRadius: BorderRadius.circular(14),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: radarHeading),
              const SizedBox(height: 6),
              Text(
                _query.isNotEmpty
                    ? 'Coba perluas rentang tanggal atau hapus sebagian filter.'
                    : 'Kegiatan yang dipublikasikan organisasi akan muncul sesuai periode yang dipilih.',
              ),
              if (_query.isNotEmpty || _period != RadarPeriod.all)
                TextButton(
                  onPressed: () => _reset(all: true),
                  child: const Text('Lihat semua kegiatan publik'),
                ),
            ],
          ),
        ),
      ];
    }
    final discovery = result.events.where((event) => !event.isVerification);
    final verification = result.events.where((event) => event.isVerification);
    return [
      for (final (label, events) in [
        (
          'Mendatang & sedang berlangsung',
          discovery.where((event) => !event.isHistorical(result.reference)),
        ),
        (
          'Riwayat kegiatan',
          discovery.where((event) => event.isHistorical(result.reference)),
        ),
      ])
        if (events.isNotEmpty) ...[
          Text(label, style: radarHeading),
          const SizedBox(height: 8),
          for (final event in events) _eventCard(event, result),
        ],
      if (verification.isNotEmpty)
        ExpansionTile(
          title: const Text('Data verifikasi teknis'),
          children: [
            for (final event in verification) _eventCard(event, result),
          ],
        ),
      if (result.lastPage > 1)
        Wrap(
          alignment: WrapAlignment.spaceBetween,
          crossAxisAlignment: WrapCrossAlignment.center,
          spacing: 8,
          children: [
            TextButton(
              onPressed: result.page > 1
                  ? () => _load(page: result.page - 1, moveToTop: true)
                  : null,
              child: const Text('Sebelumnya'),
            ),
            Text(
              '${result.page} / ${result.lastPage}',
              semanticsLabel: 'Halaman ${result.page} dari ${result.lastPage}',
            ),
            TextButton(
              onPressed: result.page < result.lastPage
                  ? () => _load(page: result.page + 1, moveToTop: true)
                  : null,
              child: const Text('Berikutnya'),
            ),
          ],
        ),
    ];
  }

  Widget _eventCard(PublicEvent event, RadarPage result) => Padding(
    padding: const EdgeInsets.only(bottom: 14),
    child: RadarEventCard(
      event: event,
      api: widget.api,
      reference: result.reference,
      onOpen: () => _open(event),
    ),
  );
}
