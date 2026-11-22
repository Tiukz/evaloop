import 'package:material_ui/material_ui.dart';

import '../features/auth/auth_api.dart';
import '../features/auth/auth_session.dart';
import 'evaloop_widgets.dart';

class ApiResult<T> extends StatelessWidget {
  const ApiResult({
    super.key,
    required this.future,
    required this.onRetry,
    required this.content,
  });

  final Future<T> future;
  final VoidCallback onRetry;
  final Widget Function(T) content;

  @override
  Widget build(BuildContext context) => FutureBuilder<T>(
    future: future,
    builder: (context, snapshot) {
      if (snapshot.connectionState != ConnectionState.done) {
        return const LinearProgressIndicator(semanticsLabel: 'Memuat data');
      }
      if (snapshot.hasData) return content(snapshot.data as T);
      final error = snapshot.error;
      final status = error is ApiFailure ? error.status : null;
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          InlineNotice(
            error is ApiFailure ? error.message : 'Data tidak dapat dimuat.',
          ),
          if (status != 401 && status != 403 && status != 404)
            TextButton(onPressed: onRetry, child: const Text('Coba lagi')),
        ],
      );
    },
  );
}

Future<T> readWithSession<T>(AuthSession session, Future<T> future) async {
  try {
    return await future;
  } on ApiFailure catch (error) {
    if (error.status == 401) session.expire();
    rethrow;
  }
}
