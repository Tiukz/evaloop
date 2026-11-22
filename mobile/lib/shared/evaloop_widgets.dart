import 'package:material_ui/material_ui.dart';

import '../app/evaloop_theme.dart';

String displayName(String text) => text.replaceFirst(
  RegExp(r'^\[(SIMULASI|TECFEST)\]\s*', caseSensitive: false),
  '',
);

class EvaloopWordmark extends StatelessWidget {
  const EvaloopWordmark({super.key, this.size = 23});

  final double size;

  @override
  Widget build(BuildContext context) => Text.rich(
    const TextSpan(
      text: 'evaloop',
      children: [
        TextSpan(
          text: '.',
          style: TextStyle(color: EvaloopTheme.teal),
        ),
      ],
    ),
    semanticsLabel: 'Evaloop',
    style: TextStyle(
      fontFamily: 'Manrope',
      fontSize: size,
      height: 1,
      fontWeight: FontWeight.w600,
      letterSpacing: -size * .065,
      color: EvaloopTheme.ink,
    ),
  );
}

class InlineNotice extends StatelessWidget {
  const InlineNotice(this.message, {super.key});

  final String message;

  @override
  Widget build(BuildContext context) => Semantics(
    liveRegion: true,
    child: Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: EvaloopTheme.dangerSoft,
        border: Border.all(color: const Color(0xFFEFCCC8)),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(message, style: const TextStyle(color: EvaloopTheme.danger)),
    ),
  );
}
