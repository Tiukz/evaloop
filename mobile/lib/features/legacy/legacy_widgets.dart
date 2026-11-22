import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_session.dart';
import 'legacy_api.dart';

const legacyTint = Color(0xFFEDF4F8);
const legacyInk = Color(0xFF286F91);
const legacyHeading = TextStyle(
  fontFamily: 'Geist',
  fontSize: 18,
  fontWeight: FontWeight.w600,
  height: 1.3,
  color: EvaloopTheme.ink,
);

class LegacyRoute extends StatelessWidget {
  const LegacyRoute({
    super.key,
    required this.session,
    required this.accountId,
    required this.backLabel,
    required this.children,
  });

  final AuthSession session;
  final int? accountId;
  final String backLabel;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: ListenableBuilder(
        listenable: session,
        builder: (context, child) => ListView(
          padding: const EdgeInsets.fromLTRB(14, 12, 14, 28),
          children: [
            Align(
              alignment: Alignment.centerLeft,
              child: TextButton.icon(
                onPressed: () => Navigator.of(context).pop(),
                icon: const Icon(Icons.arrow_back, size: 16),
                label: Text(backLabel),
              ),
            ),
            const SizedBox(height: 8),
            if (session.phase == SessionPhase.checking)
              const LinearProgressIndicator(semanticsLabel: 'Memeriksa sesi')
            else if (session.phase == SessionPhase.signedIn &&
                session.profile?.id == accountId)
              ...children
            else
              InlineNotice(
                session.message ??
                    'Silakan masuk kembali untuk membuka Legacy Insights.',
              ),
          ],
        ),
      ),
    ),
  );
}

class LegacyDocument extends StatelessWidget {
  const LegacyDocument({super.key, required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(17),
    decoration: BoxDecoration(
      color: Colors.white,
      border: Border.all(color: EvaloopTheme.border),
      borderRadius: BorderRadius.circular(12),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: children,
    ),
  );
}

class LegacyPagination extends StatelessWidget {
  const LegacyPagination(this.page, {super.key, required this.goTo});

  final LegacyPage<Object> page;
  final ValueChanged<int> goTo;

  @override
  Widget build(BuildContext context) {
    if (page.lastPage <= 1) return const SizedBox.shrink();
    return Wrap(
      alignment: WrapAlignment.spaceBetween,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: [
        TextButton(
          onPressed: page.page > 1 ? () => goTo(page.page - 1) : null,
          child: const Text('Sebelumnya'),
        ),
        Text(
          '${page.page} / ${page.lastPage}',
          semanticsLabel: 'Halaman ${page.page} dari ${page.lastPage}',
        ),
        TextButton(
          onPressed: page.page < page.lastPage
              ? () => goTo(page.page + 1)
              : null,
          child: const Text('Berikutnya'),
        ),
      ],
    );
  }
}
