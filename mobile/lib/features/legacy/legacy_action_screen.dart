import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../core/evaloop_time.dart';
import '../../shared/api_result.dart';
import '../../shared/evaloop_widgets.dart';
import '../auth/auth_api.dart';
import '../auth/auth_session.dart';
import 'legacy_api.dart';
import 'legacy_widgets.dart';

class LegacyActionScreen extends StatefulWidget {
  const LegacyActionScreen({
    super.key,
    required this.api,
    required this.session,
    required this.eventId,
    required this.lesson,
  });

  final LegacyApi api;
  final AuthSession session;
  final int eventId;
  final LegacyLesson lesson;

  @override
  State<LegacyActionScreen> createState() => _LegacyActionScreenState();
}

class _LegacyActionScreenState extends State<LegacyActionScreen> {
  final _formKey = GlobalKey<FormState>();
  late final int? _account;
  late final TextEditingController _title;
  final _description = TextEditingController();
  late final String _initialTitle;
  late Future<LegacyActionForm> _form;
  int? _assignee;
  DateTime? _due;
  bool _hasDraft = false;
  bool _generating = false;
  bool _saving = false;
  String? _message;

  bool get _active =>
      mounted &&
      widget.session.phase == SessionPhase.signedIn &&
      widget.session.profile?.id == _account;

  @override
  void initState() {
    super.initState();
    _account = widget.session.profile?.id;
    _initialTitle = String.fromCharCodes(
      displayName(widget.lesson.recommendation).runes.take(200),
    );
    _title = TextEditingController(text: _initialTitle);
    _form = _fetch();
  }

  Future<LegacyActionForm> _fetch() => readWithSession(
    widget.session,
    widget.api.actionForm(widget.eventId, widget.lesson.id),
  );

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    super.dispose();
  }

  Future<void> _generate() async {
    if (_generating || _saving) return;
    if (_hasDraft || _title.text != _initialTitle) {
      final replace = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Ganti isian draf?'),
          content: const Text(
            'Draf baru akan menggantikan judul dan deskripsi saat ini.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Batal'),
            ),
            TextButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Ganti'),
            ),
          ],
        ),
      );
      if (replace != true || !_active) return;
    }

    final titleBefore = _title.text;
    final descriptionBefore = _description.text;
    setState(() {
      _generating = true;
      _message = null;
    });
    try {
      final draft = await readWithSession(
        widget.session,
        widget.api.draft(widget.eventId, widget.lesson.id),
      );
      if (!_active) return;
      setState(() {
        if (_title.text != titleBefore ||
            _description.text != descriptionBefore) {
          _message =
              'Isian Anda berubah selama AI bekerja dan tetap dipertahankan.';
        } else {
          _title.text = draft.title;
          _description.text = draft.description;
          _hasDraft = true;
        }
      });
    } on ApiFailure catch (error) {
      if (_active) {
        setState(
          () => _message = switch (error.status) {
            409 => 'Tindak lanjut sudah dibuat. Kembali ke evaluasi untuk melihatnya.',
            403 || 429 => error.message,
            _ =>
              'Draf AI belum tersedia. Coba lagi atau isi tugas secara manual.',
          },
        );
      }
    } finally {
      if (mounted) setState(() => _generating = false);
    }
  }

  Future<void> _pickDue(DateTime startsAt) async {
    final start = EvaloopTime.wita(startsAt);
    final lastDay = DateTime(start.year, start.month, start.day);
    final now = EvaloopTime.wita(DateTime.now());
    final today = DateTime(now.year, now.month, now.day);
    final initial = _due ?? (today.isAfter(lastDay) ? lastDay : today);
    final date = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(start.year - 100),
      lastDate: lastDay,
    );
    if (date == null || !mounted || !_active) return;
    final time = await showTimePicker(
      context: context,
      initialTime: _due == null
          ? const TimeOfDay(hour: 9, minute: 0)
          : TimeOfDay.fromDateTime(_due!),
    );
    if (time == null || !_active) return;

    final due = DateTime.utc(
      date.year,
      date.month,
      date.day,
      time.hour,
      time.minute,
    );
    if (!due.isBefore(start)) {
      setState(() => _message = 'Tenggat harus sebelum kegiatan dimulai.');
      return;
    }
    setState(() {
      _due = due;
      _message = null;
    });
  }

  Future<void> _save() async {
    if (_saving || _generating || !_formKey.currentState!.validate()) return;
    final due = _due;
    if (due == null) {
      setState(() => _message = 'Pilih tenggat dalam WITA.');
      return;
    }
    setState(() {
      _saving = true;
      _message = null;
    });
    try {
      await readWithSession(
        widget.session,
        widget.api.save(
          widget.eventId,
          widget.lesson.id,
          title: _title.text.trim(),
          assigneeId: _assignee!,
          dueAt: '${EvaloopTime.parameter(due)}T${EvaloopTime.clock(due)}',
        ),
      );
      if (mounted && _active) Navigator.of(context).pop();
    } on ApiFailure catch (error) {
      if (_active) setState(() => _message = error.message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) => LegacyRoute(
    session: widget.session,
    accountId: _account,
    backLabel: 'Sumber evaluasi',
    children: [
      const Text('Buat Tindak Lanjut', style: EvaloopTheme.heading),
      const SizedBox(height: 14),
      LegacyDocument(
        children: [
          Text(displayName(widget.lesson.source.title), style: legacyHeading),
          const SizedBox(height: 12),
          const Text(
            'Permasalahan',
            style: TextStyle(fontWeight: FontWeight.w600),
          ),
          Text(
            displayName(widget.lesson.problem),
            style: const TextStyle(fontSize: 16, height: 1.55),
          ),
          const SizedBox(height: 12),
          const Text(
            'Rekomendasi',
            style: TextStyle(fontWeight: FontWeight.w600),
          ),
          Text(
            displayName(widget.lesson.recommendation),
            style: const TextStyle(fontSize: 16, height: 1.55),
          ),
        ],
      ),
      const SizedBox(height: 16),
      ApiResult(
        future: _form,
        onRetry: () => setState(() => _form = _fetch()),
        content: (data) => Form(
          key: _formKey,
          child: LegacyDocument(
            children: [
              Text(displayName(data.event.title), style: legacyHeading),
              const SizedBox(height: 12),
              TextFormField(
                controller: _title,
                enabled: !_saving,
                maxLength: 200,
                decoration: const InputDecoration(labelText: 'Judul tugas'),
                validator: (value) => value == null || value.trim().isEmpty
                    ? 'Masukkan judul tugas.'
                    : null,
              ),
              Align(
                alignment: Alignment.centerLeft,
                child: OutlinedButton.icon(
                  onPressed: _generating || _saving ? null : _generate,
                  icon: const Icon(Icons.auto_awesome, size: 16),
                  label: Text(
                    _generating ? 'Menyusun draf…' : 'Bantu Susun dengan AI',
                  ),
                ),
              ),
              if (_hasDraft) ...[
                const SizedBox(height: 10),
                TextField(
                  controller: _description,
                  enabled: !_saving,
                  minLines: 3,
                  maxLines: 7,
                  maxLength: 1200,
                  decoration: const InputDecoration(
                    labelText: 'Draf deskripsi (untuk referensi)',
                  ),
                ),
              ],
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
                isExpanded: true,
                decoration: const InputDecoration(labelText: 'PIC'),
                items: [
                  for (final member in data.members)
                    DropdownMenuItem(
                      value: member.id,
                      child: Text(
                        displayName(member.name),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                ],
                onChanged: _saving ? null : (value) => _assignee = value,
                validator: (value) =>
                    value == null ? 'Pilih penanggung jawab.' : null,
              ),
              const SizedBox(height: 10),
              Align(
                alignment: Alignment.centerLeft,
                child: TextButton.icon(
                  onPressed: _saving
                      ? null
                      : () => _pickDue(data.event.startsAt),
                  icon: const Icon(Icons.calendar_today_outlined, size: 16),
                  label: Text(
                    _due == null
                        ? 'Pilih tenggat (WITA)'
                        : '${EvaloopTime.shortDate(_due!)} · ${EvaloopTime.clock(_due!)} WITA',
                  ),
                ),
              ),
              if (_message case final message?) InlineNotice(message),
              const SizedBox(height: 12),
              Align(
                alignment: Alignment.centerLeft,
                child: FilledButton(
                  onPressed: _saving || _generating ? null : _save,
                  child: Text(_saving ? 'Menyimpan…' : 'Simpan tindak lanjut'),
                ),
              ),
            ],
          ),
        ),
      ),
    ],
  );
}
