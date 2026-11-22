import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';

class WorkspaceStatus extends StatelessWidget {
  const WorkspaceStatus(this.status, {super.key});
  final String status;

  @override
  Widget build(BuildContext context) {
    final (label, ink, fill) = switch (status) {
      'published' => (
        'Dipublikasikan',
        const Color(0xFF167347),
        const Color(0xFFEAF7EE),
      ),
      'completed' => ('Selesai', EvaloopTheme.muted, const Color(0xFFEEF2F5)),
      'cancelled' => (
        'Dibatalkan',
        const Color(0xFF986000),
        const Color(0xFFFFF4D8),
      ),
      'draft' => ('Draft', EvaloopTheme.muted, const Color(0xFFEEF2F5)),
      _ => (status, EvaloopTheme.muted, const Color(0xFFEEF2F5)),
    };
    return DecoratedBox(
      decoration: BoxDecoration(
        color: fill,
        borderRadius: BorderRadius.circular(30),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
        child: Text(
          label,
          style: TextStyle(
            color: ink,
            fontSize: 12,
            fontWeight: FontWeight.w500,
          ),
        ),
      ),
    );
  }
}
