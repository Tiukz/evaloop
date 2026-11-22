import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../shared/api_result.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_session.dart';
import '../workspace/workspace_status.dart';
import 'meeting_models.dart';

const meetingHeading = TextStyle(
  fontFamily: 'Geist',
  fontSize: 18,
  fontWeight: FontWeight.w600,
  height: 1.3,
);
const meetingCyan = Color(0xFF286F91);

class MeetingPage<T> extends StatelessWidget {
  const MeetingPage({
    super.key,
    required this.session,
    required this.accountId,
    required this.backLabel,
    required this.future,
    required this.reload,
    required this.content,
  });

  final AuthSession session;
  final int? accountId;
  final String backLabel;
  final Future<T> future;
  final Future<void> Function() reload;
  final Widget Function(T) content;

  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: ListenableBuilder(
        listenable: session,
        builder: (context, _) {
          final signedIn =
              session.phase == SessionPhase.signedIn &&
              session.profile?.id == accountId;
          return RefreshIndicator(
            onRefresh: reload,
            notificationPredicate: (notification) =>
                signedIn && notification.depth == 0,
            child: ListView(
              keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(14, 12, 14, 28),
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Align(
                        alignment: Alignment.centerLeft,
                        child: TextButton.icon(
                          onPressed: () => Navigator.of(context).pop(),
                          icon: const Icon(Icons.arrow_back, size: 16),
                          style: TextButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 4),
                          ),
                          label: Text(backLabel),
                        ),
                      ),
                    ),
                    if (signedIn)
                      IconButton(
                        tooltip: 'Muat ulang Meeting Hub',
                        onPressed: reload,
                        icon: const Icon(
                          Icons.refresh,
                          color: EvaloopTheme.teal,
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 8),
                if (session.phase == SessionPhase.checking)
                  const LinearProgressIndicator(
                    semanticsLabel: 'Memeriksa sesi',
                  )
                else if (!signedIn)
                  InlineNotice(
                    session.message ??
                        'Silakan masuk kembali untuk membuka Meeting Hub.',
                  )
                else ...[
                  ApiResult(future: future, onRetry: reload, content: content),
                ],
              ],
            ),
          );
        },
      ),
    ),
  );
}

class MeetingEventContext extends StatelessWidget {
  const MeetingEventContext(this.event, {super.key});

  final MeetingEvent event;

  @override
  Widget build(BuildContext context) => DecoratedBox(
    decoration: BoxDecoration(
      color: EvaloopTheme.ice,
      border: Border.all(color: EvaloopTheme.border),
      borderRadius: BorderRadius.circular(12),
    ),
    child: Padding(
      padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 12),
      child: Wrap(
        alignment: WrapAlignment.spaceBetween,
        spacing: 10,
        runSpacing: 6,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          Text(
            displayName(event.title),
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
          WorkspaceStatus(event.status),
        ],
      ),
    ),
  );
}

RoundedRectangleBorder meetingBorder() => RoundedRectangleBorder(
  side: const BorderSide(color: EvaloopTheme.border),
  borderRadius: BorderRadius.circular(12),
);
