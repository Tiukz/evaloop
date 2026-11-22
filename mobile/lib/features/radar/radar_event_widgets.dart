import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../core/evaloop_time.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_api.dart';
import 'radar_api.dart';

const radarHeading = TextStyle(
  fontFamily: 'Geist',
  fontSize: 18,
  fontWeight: FontWeight.w600,
  height: 1.3,
  color: EvaloopTheme.ink,
);

extension EventPresentation on PublicEvent {
  DateTime get start => EvaloopTime.wita(startsAt);
  DateTime get end => EvaloopTime.wita(endsAt);
  String get scope => displayName(program ?? faculty ?? 'Tingkat universitas');
  String get location => venue == null || venue!.trim().isEmpty
      ? 'Lokasi belum ditentukan'
      : displayName(venue!);
  bool get isVerification =>
      RegExp(r'^\[SIMULASI\] Event Verifikasi \d{8}-\d{6}$').hasMatch(title);
}

class RadarPoster extends StatelessWidget {
  const RadarPoster({
    super.key,
    required this.event,
    required this.api,
    required this.historical,
    this.detail = false,
  });

  final PublicEvent event;
  final RadarApi api;
  final bool historical;
  final bool detail;

  @override
  Widget build(BuildContext context) {
    final url = api.poster(event);
    final poster = url == null
        ? _artwork()
        : Image.network(
            url.toString(),
            fit: BoxFit.contain,
            semanticLabel: 'Poster ${displayName(event.title)}',
            errorBuilder: (context, error, stack) => _artwork(),
            frameBuilder: (context, child, frame, synchronous) =>
                frame == null ? _artwork() : child,
          );
    return AspectRatio(
      aspectRatio: detail ? 5 / 4 : 3 / 2,
      child: ClipRect(
        child: ColoredBox(
          color: EvaloopTheme.ice,
          child: historical
              ? ColorFiltered(
                  colorFilter: const ColorFilter.matrix([
                    .567,
                    .393,
                    .04,
                    0,
                    0,
                    .117,
                    .843,
                    .04,
                    0,
                    0,
                    .117,
                    .393,
                    .49,
                    0,
                    0,
                    0,
                    0,
                    0,
                    1,
                    0,
                  ]),
                  child: poster,
                )
              : poster,
        ),
      ),
    );
  }

  Widget _artwork() {
    final variant = event.organizationId % 3;
    final (background, foreground, accent) = switch (variant) {
      1 => (
        const Color(0xFFCCEAE2),
        const Color(0xFF103C3A),
        const Color(0xFF6CBDAC),
      ),
      2 => (
        const Color(0xFF0D665F),
        const Color(0xFFF1FAF6),
        const Color(0xFFA6E0CE),
      ),
      _ => (
        const Color(0xFF112733),
        const Color(0xFFECFAF5),
        const Color(0xFF38B7A2),
      ),
    };
    final title = displayName(event.title).trim();
    final breakAt = title.indexOf(RegExp(r'\s'));
    final first = breakAt < 0 ? title : title.substring(0, breakAt);
    final rest = breakAt < 0 ? '' : title.substring(breakAt).trimLeft();
    final start = event.start;

    return ExcludeSemantics(
      child: ColoredBox(
        color: background,
        child: Stack(
          fit: StackFit.expand,
          children: [
            Positioned(
              right: variant == 1 ? -54 : -88,
              top: 35,
              child: Transform.rotate(
                angle: variant == 1 ? .49 : 0,
                child: Container(
                  width: 205,
                  height: variant == 1 ? 226 : 205,
                  decoration: BoxDecoration(
                    shape: variant == 1 ? BoxShape.rectangle : BoxShape.circle,
                    border: Border.all(
                      color: accent.withValues(alpha: .50),
                      width: variant == 2 ? 1 : 22,
                    ),
                    boxShadow: variant == 2
                        ? [
                            BoxShadow(
                              color: accent.withValues(alpha: .13),
                              spreadRadius: 17,
                            ),
                            BoxShadow(
                              color: accent.withValues(alpha: .07),
                              spreadRadius: 34,
                            ),
                          ]
                        : null,
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: DefaultTextStyle(
                style: TextStyle(
                  color: foreground,
                  fontFamily: 'Inter',
                  fontSize: 12,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Expanded(
                          child: Text(
                            'evaloop.',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontFamily: 'Manrope',
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        Text('${start.year}'),
                      ],
                    ),
                    Expanded(
                      child: Align(
                        alignment: Alignment.centerLeft,
                        child: FittedBox(
                          fit: BoxFit.scaleDown,
                          alignment: Alignment.centerLeft,
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                first,
                                style: TextStyle(
                                  fontFamily: 'Manrope',
                                  fontSize: detail ? 42 : 38,
                                  fontWeight: FontWeight.w600,
                                  height: 1.05,
                                  letterSpacing: -1.5,
                                ),
                              ),
                              if (rest.isNotEmpty)
                                ConstrainedBox(
                                  constraints: const BoxConstraints(
                                    maxWidth: 270,
                                  ),
                                  child: Text(
                                    rest,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      fontFamily: 'Manrope',
                                      fontSize: 24,
                                      height: 1.12,
                                    ),
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            event.scope,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          '${EvaloopTime.pad(start.day)} / ${EvaloopTime.pad(start.month)}',
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class RadarStatus extends StatelessWidget {
  const RadarStatus(this.label, {super.key, required this.active});

  final String label;
  final bool active;

  @override
  Widget build(BuildContext context) => DecoratedBox(
    decoration: BoxDecoration(
      color: active ? EvaloopTheme.tealSoft : EvaloopTheme.ice,
      borderRadius: BorderRadius.circular(30),
    ),
    child: Padding(
      padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 12,
          color: active ? EvaloopTheme.teal : EvaloopTheme.muted,
        ),
      ),
    ),
  );
}

class RadarEventCard extends StatelessWidget {
  const RadarEventCard({
    super.key,
    required this.event,
    required this.api,
    required this.reference,
    required this.onOpen,
  });

  final PublicEvent event;
  final RadarApi api;
  final DateTime reference;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    final historical = event.isHistorical(reference);
    final start = event.start;
    final end = event.end;
    final endTime = EvaloopTime.sameDay(start, end)
        ? EvaloopTime.clock(end)
        : '${EvaloopTime.shortDate(end)} ${EvaloopTime.clock(end)}';
    final description = event.description;
    return Material(
      color: Colors.white,
      shape: RoundedRectangleBorder(
        side: const BorderSide(color: EvaloopTheme.border),
        borderRadius: BorderRadius.circular(16),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onOpen,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            RadarPoster(event: event, api: api, historical: historical),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Wrap(
                    spacing: 10,
                    runSpacing: 4,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    children: [
                      Text(
                        EvaloopTime.shortDate(start),
                        style: const TextStyle(
                          fontSize: 12,
                          color: EvaloopTheme.teal,
                        ),
                      ),
                      RadarStatus(event.state(reference), active: !historical),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(displayName(event.title), style: radarHeading),
                  Text(displayName(event.organizer)),
                  if (description != null && description.trim().isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(
                      displayName(description),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: EvaloopTheme.muted),
                    ),
                  ],
                  const SizedBox(height: 12),
                  RadarFact(
                    Icons.calendar_today_outlined,
                    '${EvaloopTime.clock(start)} – $endTime WITA',
                  ),
                  const SizedBox(height: 4),
                  RadarFact(Icons.location_on_outlined, event.location),
                ],
              ),
            ),
            const Divider(height: 1, color: EvaloopTheme.border),
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 12, horizontal: 16),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      'Lihat detail',
                      style: TextStyle(
                        color: EvaloopTheme.teal,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  Icon(Icons.arrow_forward, size: 18, color: EvaloopTheme.teal),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class RadarFact extends StatelessWidget {
  const RadarFact(this.icon, this.text, {super.key});
  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) => Row(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Padding(
        padding: const EdgeInsets.only(top: 2),
        child: Icon(icon, size: 16, color: EvaloopTheme.muted),
      ),
      const SizedBox(width: 8),
      Expanded(child: Text(text)),
    ],
  );
}

class RadarFailure extends StatelessWidget {
  const RadarFailure(this.error, {super.key, required this.onRetry});
  final ApiFailure error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      InlineNotice(error.message),
      const SizedBox(height: 10),
      OutlinedButton(onPressed: onRetry, child: const Text('Coba lagi')),
    ],
  );
}
