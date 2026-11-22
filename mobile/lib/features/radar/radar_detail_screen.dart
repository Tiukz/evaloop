import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../core/evaloop_time.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_api.dart';
import 'radar_api.dart';
import 'radar_event_widgets.dart';

class RadarDetailScreen extends StatefulWidget {
  const RadarDetailScreen({
    super.key,
    required this.api,
    required this.eventId,
    required this.reference,
    this.backLabel = 'Kembali ke Event Radar',
  });

  final RadarApi api;
  final int eventId;
  final DateTime reference;
  final String backLabel;

  @override
  State<RadarDetailScreen> createState() => _RadarDetailScreenState();
}

class _RadarDetailScreenState extends State<RadarDetailScreen> {
  late Future<PublicEvent> _event;

  @override
  void initState() {
    super.initState();
    _event = widget.api.detail(widget.eventId);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 28),
        children: [
          Align(
            alignment: Alignment.centerLeft,
            child: TextButton.icon(
              onPressed: () => Navigator.of(context).pop(),
              icon: const Icon(Icons.arrow_back, size: 18),
              label: Text(widget.backLabel),
              style: TextButton.styleFrom(
                minimumSize: const Size(0, 44),
                padding: const EdgeInsets.symmetric(horizontal: 4),
              ),
            ),
          ),
          const SizedBox(height: 8),
          FutureBuilder<PublicEvent>(
            future: _event,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const LinearProgressIndicator(
                  semanticsLabel: 'Memuat detail kegiatan',
                );
              }
              if (snapshot.data case final event?) return _content(event);
              final error = snapshot.error;
              return RadarFailure(
                error is ApiFailure
                    ? error
                    : const ApiFailure('Detail kegiatan tidak dapat dimuat.'),
                onRetry: () =>
                    setState(() => _event = widget.api.detail(widget.eventId)),
              );
            },
          ),
        ],
      ),
    ),
  );

  Widget _content(PublicEvent event) {
    final historical = event.isHistorical(widget.reference);
    final start = event.start;
    final end = event.end;
    final date = EvaloopTime.sameDay(start, end)
        ? EvaloopTime.longDate(start)
        : '${EvaloopTime.longDate(start)} – ${EvaloopTime.longDate(end)}';
    final time = '${EvaloopTime.clock(start)} – ${EvaloopTime.clock(end)} WITA';
    final description = event.description;
    final organizer = displayName(event.organizer);
    final badge = switch (event.status) {
      'completed' => 'Selesai',
      'cancelled' => 'Dibatalkan',
      _ => 'Dipublikasikan',
    };

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(18),
          child: RadarPoster(
            event: event,
            api: widget.api,
            historical: historical,
            detail: true,
          ),
        ),
        const SizedBox(height: 18),
        Wrap(
          spacing: 8,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            RadarStatus(badge, active: event.status == 'published'),
            if (event.status == 'published' && historical)
              const Text(
                'Jadwal berlalu',
                style: TextStyle(color: EvaloopTheme.muted, fontSize: 12),
              ),
          ],
        ),
        const SizedBox(height: 8),
        Text(displayName(event.title), style: EvaloopTheme.heading),
        const SizedBox(height: 6),
        Text(
          organizer,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
        Text(event.scope, style: const TextStyle(color: EvaloopTheme.muted)),
        const SizedBox(height: 18),
        _fact(Icons.calendar_today_outlined, 'Jadwal kegiatan', [date, time]),
        const SizedBox(height: 10),
        _fact(Icons.location_on_outlined, 'Lokasi', [event.location]),
        if (event.status == 'cancelled') ...[
          const SizedBox(height: 12),
          const Text(
            'Kegiatan dibatalkan. Informasi ini ditampilkan sebagai riwayat publik.',
          ),
        ],
        const SizedBox(height: 22),
        const Divider(color: EvaloopTheme.border),
        const SizedBox(height: 12),
        const Text('Tentang kegiatan', style: radarHeading),
        const SizedBox(height: 8),
        Text(
          description == null || description.trim().isEmpty
              ? 'Deskripsi belum tersedia.'
              : description,
        ),
        const SizedBox(height: 20),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: EvaloopTheme.ice,
            border: Border.all(color: EvaloopTheme.border),
            borderRadius: BorderRadius.circular(14),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Diselenggarakan oleh', style: radarHeading),
              const SizedBox(height: 8),
              Text(
                organizer,
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 12),
              _organizationItem(
                'Lingkup organisasi',
                event.program != null
                    ? 'Program studi'
                    : event.faculty != null
                    ? 'Fakultas'
                    : 'Universitas',
              ),
              if (event.faculty case final faculty?)
                _organizationItem('Fakultas', displayName(faculty)),
              if (event.program case final program?)
                _organizationItem('Program studi', displayName(program)),
            ],
          ),
        ),
      ],
    );
  }

  Widget _fact(IconData icon, String label, List<String> values) => Container(
    padding: const EdgeInsets.all(12),
    decoration: BoxDecoration(
      color: Colors.white,
      border: Border.all(color: EvaloopTheme.border),
      borderRadius: BorderRadius.circular(12),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        DecoratedBox(
          decoration: BoxDecoration(
            color: EvaloopTheme.mist,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Padding(
            padding: const EdgeInsets.all(8),
            child: Icon(icon, size: 18, color: EvaloopTheme.teal),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(color: EvaloopTheme.muted, fontSize: 12),
              ),
              for (final value in values) Text(value),
            ],
          ),
        ),
      ],
    ),
  );

  Widget _organizationItem(String label, String value) => Padding(
    padding: const EdgeInsets.only(top: 4),
    child: Text.rich(
      TextSpan(
        children: [
          TextSpan(
            text: '$label · ',
            style: const TextStyle(color: EvaloopTheme.muted),
          ),
          TextSpan(text: value),
        ],
      ),
    ),
  );
}
