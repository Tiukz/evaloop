import 'dart:ui' as ui;

import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../core/evaloop_time.dart';
import '../../shared/api_result.dart';
import '../../shared/evaloop_widgets.dart';
import '../../shared/private_picture.dart';
import '../auth/auth_session.dart';
import '../tasks/task_widgets.dart';
import 'legacy_action_screen.dart';
import 'legacy_api.dart';
import 'legacy_widgets.dart';

class LegacyScreen extends StatefulWidget {
  const LegacyScreen({
    super.key,
    required this.eventId,
    required this.api,
    required this.session,
    required this.onTask,
    this.backLabel = 'Kembali ke Event Saya',
  });

  final int eventId;
  final LegacyApi api;
  final String backLabel;
  final AuthSession session;
  final Future<void> Function(BuildContext, int) onTask;

  @override
  State<LegacyScreen> createState() => _LegacyScreenState();
}

class _LegacyScreenState extends State<LegacyScreen> {
  late final int? _account = widget.session.profile?.id;
  final _search = TextEditingController();
  late Future<LegacyCollection> _collection;
  String _query = '';
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _collection = _fetch();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<LegacyCollection> _fetch() => readWithSession(
    widget.session,
    widget.api.lessons(widget.eventId, _page, _query),
  );

  void _load(int page) => setState(() {
    _page = page;
    _collection = _fetch();
  });

  void _submit() {
    _query = _search.text.trim();
    FocusScope.of(context).unfocus();
    _load(1);
  }

  @override
  Widget build(BuildContext context) => LegacyRoute(
    session: widget.session,
    accountId: _account,
    backLabel: widget.backLabel,
    children: [
      ApiResult(
        future: _collection,
        onRetry: () => _load(_page),
        content: _content,
      ),
    ],
  );

  Widget _content(LegacyCollection collection) {
    final editions = <int, List<LegacyLesson>>{};
    for (final lesson in collection.lessons.items) {
      editions.putIfAbsent(lesson.source.id, () => []).add(lesson);
    }
    final series = collection.event.series;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          displayName(collection.event.title),
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
        if (series != null)
          Text(
            displayName(series),
            style: const TextStyle(color: EvaloopTheme.muted),
          ),
        const SizedBox(height: 12),
        DecoratedBox(
          decoration: BoxDecoration(
            color: legacyTint,
            border: Border.all(color: EvaloopTheme.border),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Legacy Insights', style: EvaloopTheme.heading),
                const SizedBox(height: 12),
                const Divider(height: 1, color: EvaloopTheme.border),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 24,
                  runSpacing: 12,
                  children: [
                    _count(collection.inherited, 'Evaluasi diwariskan'),
                    _count(collection.activeFollowUps, 'Tindak lanjut aktif'),
                  ],
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _search,
                maxLength: 120,
                textInputAction: TextInputAction.search,
                onSubmitted: (_) => _submit(),
                decoration: const InputDecoration(
                  counterText: '',
                  hintText: 'Cari masalah atau rekomendasi',
                  prefixIcon: Icon(Icons.search, size: 18),
                ),
              ),
            ),
            const SizedBox(width: 8),
            OutlinedButton(onPressed: _submit, child: const Text('Cari')),
          ],
        ),
        const SizedBox(height: 22),
        const Text('Pembelajaran dari edisi sebelumnya', style: legacyHeading),
        const SizedBox(height: 12),
        if (editions.isEmpty)
          DecoratedBox(
            decoration: BoxDecoration(
              color: legacyTint,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Text(
                _query.isEmpty
                    ? 'Belum ada evaluasi yang diwariskan ke edisi ini.'
                    : 'Tidak ada evaluasi warisan yang cocok.',
              ),
            ),
          ),
        for (final lessons in editions.values) ...[
          Text(
            'Evaluasi dari ${displayName(lessons.first.source.title)}',
            style: const TextStyle(
              fontWeight: FontWeight.w500,
              color: EvaloopTheme.muted,
            ),
          ),
          const SizedBox(height: 8),
          for (final lesson in lessons) ...[
            LegacyDocument(
              children: [
                _LessonText(lesson, editorial: true),
                const SizedBox(height: 18),
                _FollowUp(lesson.followUp, _openTask),
                const SizedBox(height: 12),
                _Attribution(lesson),
                Align(
                  alignment: Alignment.centerLeft,
                  child: TextButton(
                    onPressed: () async {
                      await Navigator.of(context).push<void>(
                        MaterialPageRoute(
                          builder: (_) => _LegacyDetail(
                            api: widget.api,
                            session: widget.session,
                            eventId: widget.eventId,
                            lessonId: lesson.id,
                            onTask: widget.onTask,
                          ),
                        ),
                      );
                      if (mounted &&
                          widget.session.phase == SessionPhase.signedIn) {
                        _load(_page);
                      }
                    },
                    child: const Text('Lihat sumber evaluasi'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
          ],
        ],
        LegacyPagination(collection.lessons, goTo: _load),
      ],
    );
  }

  Future<void> _openTask(int id) async {
    await widget.onTask(context, id);
    if (mounted && widget.session.phase == SessionPhase.signedIn) _load(_page);
  }

  Widget _count(int value, String label) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text('$value', style: legacyHeading),
      Text(
        label,
        style: const TextStyle(color: EvaloopTheme.muted, fontSize: 12),
      ),
    ],
  );
}

class _LegacyDetail extends StatefulWidget {
  const _LegacyDetail({
    required this.api,
    required this.session,
    required this.eventId,
    required this.lessonId,
    required this.onTask,
  });

  final LegacyApi api;
  final AuthSession session;
  final int eventId;
  final int lessonId;
  final Future<void> Function(BuildContext, int) onTask;

  @override
  State<_LegacyDetail> createState() => _LegacyDetailState();
}

class _LegacyDetailState extends State<_LegacyDetail> {
  late final int? _account = widget.session.profile?.id;
  late Future<LegacyLesson> _lesson;

  @override
  void initState() {
    super.initState();
    _lesson = _fetch();
  }

  Future<LegacyLesson> _fetch() => readWithSession(
    widget.session,
    widget.api.lesson(widget.eventId, widget.lessonId),
  );

  Future<void> _openTask(int id) async {
    await widget.onTask(context, id);
    if (mounted && widget.session.phase == SessionPhase.signedIn) {
      setState(() => _lesson = _fetch());
    }
  }

  Future<void> _openAction(LegacyLesson lesson) async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => LegacyActionScreen(
          api: widget.api,
          session: widget.session,
          eventId: widget.eventId,
          lesson: lesson,
        ),
      ),
    );
    if (mounted && widget.session.phase == SessionPhase.signedIn) {
      setState(() => _lesson = _fetch());
    }
  }

  @override
  Widget build(BuildContext context) => LegacyRoute(
    session: widget.session,
    accountId: _account,
    backLabel: 'Legacy Insights',
    children: [
      const Text('Sumber evaluasi', style: EvaloopTheme.heading),
      const SizedBox(height: 14),
      ApiResult(
        future: _lesson,
        onRetry: () => setState(() => _lesson = _fetch()),
        content: (lesson) => LegacyDocument(
          children: [
            Text(displayName(lesson.source.title), style: legacyHeading),
            Text(
              'Edisi ${EvaloopTime.wita(lesson.source.startsAt).year}',
              style: const TextStyle(color: EvaloopTheme.muted),
            ),
            const SizedBox(height: 5),
            _Attribution(lesson),
            const SizedBox(height: 18),
            _LessonText(lesson),
            if (lesson.images.isNotEmpty) ...[
              const SizedBox(height: 20),
              _Gallery(
                images: lesson.images,
                load: (image) => PrivatePicture.decode(
                  readWithSession(
                    widget.session,
                    widget.api.image(widget.eventId, lesson.id, image),
                  ),
                ),
              ),
            ],
            const SizedBox(height: 20),
            const Divider(height: 1, color: EvaloopTheme.border),
            const SizedBox(height: 14),
            if (lesson.canCreateFollowUp)
              Align(
                alignment: Alignment.centerLeft,
                child: FilledButton(
                  onPressed: () => _openAction(lesson),
                  child: const Text('Buat Tindak Lanjut'),
                ),
              )
            else
              _FollowUp(lesson.followUp, _openTask),
          ],
        ),
      ),
    ],
  );
}

class _LessonText extends StatelessWidget {
  const _LessonText(this.lesson, {this.editorial = false});

  final LegacyLesson lesson;
  final bool editorial;

  @override
  Widget build(BuildContext context) {
    final problem = lesson.problem.trim();
    final shortProblem = editorial && problem.length <= 160;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Permasalahan',
          style: TextStyle(
            color: EvaloopTheme.muted,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          problem,
          style: shortProblem
              ? legacyHeading
              : const TextStyle(fontSize: 16, height: 1.55),
        ),
        const SizedBox(height: 16),
        const Text(
          'Rekomendasi',
          style: TextStyle(color: legacyInk, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 4),
        Text(
          lesson.recommendation,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w500,
            height: 1.55,
          ),
        ),
      ],
    );
  }
}

class _Attribution extends StatelessWidget {
  const _Attribution(this.lesson);

  final LegacyLesson lesson;

  @override
  Widget build(BuildContext context) => Text(
    '${displayName(lesson.author)} · Disetujui ${EvaloopTime.shortDate(EvaloopTime.wita(lesson.approvedAt))}',
    style: const TextStyle(color: EvaloopTheme.muted, fontSize: 12),
  );
}

class _FollowUp extends StatelessWidget {
  const _FollowUp(this.followUp, this.onTask);

  final LegacyFollowUp? followUp;
  final Future<void> Function(int) onTask;

  @override
  Widget build(BuildContext context) {
    final action = followUp;
    final dueAt = action?.dueAt;
    final due = dueAt == null ? null : EvaloopTime.wita(dueAt);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Tindak lanjut',
          style: TextStyle(fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 4),
        if (action == null)
          const Text(
            'Belum ada tindak lanjut.',
            style: TextStyle(color: EvaloopTheme.muted),
          )
        else ...[
          Text(
            displayName(action.title),
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 6),
          Wrap(
            spacing: 8,
            runSpacing: 6,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: [
              TaskStatusPill(action.status),
              Text(
                due == null
                    ? 'Tanpa tenggat'
                    : '${EvaloopTime.shortDate(due)} · ${EvaloopTime.clock(due)} WITA',
                style: const TextStyle(fontSize: 12, color: EvaloopTheme.muted),
              ),
            ],
          ),
          if (action.personalTaskId case final id?) ...[
            const SizedBox(height: 8),
            OutlinedButton(
              onPressed: () => onTask(id),
              child: const Text('Lihat tugas'),
            ),
          ],
        ],
      ],
    );
  }
}

class _Gallery extends StatefulWidget {
  const _Gallery({required this.images, required this.load});

  final List<LegacyImage> images;
  final Future<ui.Image> Function(LegacyImage) load;

  @override
  State<_Gallery> createState() => _GalleryState();
}

class _GalleryState extends State<_Gallery> {
  int _selected = 0;

  @override
  Widget build(BuildContext context) {
    final image = widget.images[_selected];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Text(
          'Lampiran evaluasi',
          style: TextStyle(fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        PrivatePicture(
          key: ValueKey(image.id),
          name: image.name,
          load: () => widget.load(image),
        ),
        const SizedBox(height: 6),
        Text(
          image.name,
          style: const TextStyle(fontSize: 12, color: EvaloopTheme.muted),
        ),
        if (widget.images.length > 1)
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              IconButton(
                tooltip: 'Gambar sebelumnya',
                onPressed: _selected > 0
                    ? () => setState(() => _selected--)
                    : null,
                icon: const Icon(Icons.chevron_left),
              ),
              Text('${_selected + 1} / ${widget.images.length}'),
              IconButton(
                tooltip: 'Gambar berikutnya',
                onPressed: _selected + 1 < widget.images.length
                    ? () => setState(() => _selected++)
                    : null,
                icon: const Icon(Icons.chevron_right),
              ),
            ],
          ),
      ],
    );
  }
}
