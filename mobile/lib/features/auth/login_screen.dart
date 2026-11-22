import 'package:flutter/services.dart';
import 'package:material_ui/material_ui.dart';

import '../../app/evaloop_theme.dart';
import '../../shared/evaloop_widgets.dart';
import '../radar/radar_api.dart';
import '../radar/radar_screen.dart';
import 'auth_session.dart';

class GuestShell extends StatefulWidget {
  const GuestShell({super.key, required this.session, required this.radar});

  final AuthSession session;
  final RadarApi radar;

  @override
  State<GuestShell> createState() => _GuestShellState();
}

class _GuestShellState extends State<GuestShell> {
  static const _links = MethodChannel('id.evaloop/external_links');
  bool _showRadar = true;

  Future<void> _register() async {
    final uri = widget.session.registrationUri;
    if (uri == null) return;
    try {
      await _links.invokeMethod<void>('openRegistration', uri.toString());
    } on PlatformException {
      if (mounted) _registrationUnavailable();
    } on MissingPluginException {
      if (mounted) _registrationUnavailable();
    }
  }

  void _registrationUnavailable() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Halaman pendaftaran tidak dapat dibuka.')),
    );
  }

  @override
  Widget build(BuildContext context) {
    final canRegister = widget.session.registrationUri != null;

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            DecoratedBox(
              decoration: const BoxDecoration(
                color: Colors.white,
                border: Border(bottom: BorderSide(color: EvaloopTheme.border)),
              ),
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 8,
                ),
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    final showRadarLabel =
                        constraints.maxWidth >= 330 &&
                        MediaQuery.textScalerOf(context).scale(14) <= 16;
                    final logo = InkWell(
                      onTap: () => setState(() => _showRadar = true),
                      child: const Padding(
                        padding: EdgeInsets.symmetric(vertical: 9),
                        child: EvaloopWordmark(size: 22),
                      ),
                    );
                    final radar = Tooltip(
                      message: 'Event Radar',
                      child: TextButton(
                        onPressed: () => setState(() => _showRadar = true),
                        style: TextButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 6),
                          minimumSize: const Size(36, 44),
                          foregroundColor: EvaloopTheme.muted,
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.radar, size: 16),
                            if (showRadarLabel) ...[
                              const SizedBox(width: 4),
                              const Text('Event Radar'),
                            ],
                          ],
                        ),
                      ),
                    );
                    final actions = [
                      TextButton(
                        onPressed: () => setState(() => _showRadar = false),
                        style: TextButton.styleFrom(
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                          backgroundColor: _showRadar
                              ? null
                              : EvaloopTheme.tealSoft,
                          minimumSize: const Size(0, 44),
                          padding: const EdgeInsets.symmetric(horizontal: 7),
                        ),
                        child: const Text('Masuk'),
                      ),
                      if (canRegister)
                        FilledButton(
                          onPressed: _register,
                          style: FilledButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 11),
                          ),
                          child: const Text('Daftar'),
                        ),
                    ];
                    if (MediaQuery.textScalerOf(context).scale(14) > 18 ||
                        constraints.maxWidth < 276) {
                      return Wrap(
                        spacing: 8,
                        crossAxisAlignment: WrapCrossAlignment.center,
                        children: [logo, radar, ...actions],
                      );
                    }
                    return Row(
                      children: [
                        Flexible(
                          flex: 3,
                          child: FittedBox(
                            fit: BoxFit.scaleDown,
                            alignment: Alignment.centerLeft,
                            child: logo,
                          ),
                        ),
                        radar,
                        const Spacer(),
                        for (final action in actions)
                          Padding(
                            padding: const EdgeInsets.only(left: 3),
                            child: action,
                          ),
                      ],
                    );
                  },
                ),
              ),
            ),
            Expanded(
              child: _showRadar
                  ? RadarScreen(api: widget.radar)
                  : LoginForm(
                      session: widget.session,
                      onRegister: canRegister ? _register : null,
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class LoginForm extends StatefulWidget {
  const LoginForm({super.key, required this.session, this.onRegister});

  final AuthSession session;
  final VoidCallback? onRegister;

  @override
  State<LoginForm> createState() => _LoginFormState();
}

class _LoginFormState extends State<LoginForm> {
  final _form = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();

  Future<void> _submit() async {
    if (widget.session.busy || !_form.currentState!.validate()) return;
    FocusScope.of(context).unfocus();
    TextInput.finishAutofillContext(shouldSave: false);
    await widget.session.signIn(_email.text.trim(), _password.text);
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final busy = widget.session.busy;
    final message = widget.session.message;

    return ListView(
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 24),
      children: [
        Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 448),
            child: Container(
              width: double.infinity,
              decoration: BoxDecoration(
                color: Colors.white,
                border: Border.all(color: const Color(0xFFE2E8F0)),
                borderRadius: BorderRadius.circular(12),
              ),
              padding: const EdgeInsets.all(24),
              child: AutofillGroup(
                onDisposeAction: AutofillContextAction.cancel,
                child: Form(
                  key: _form,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Masuk ke Evaloop',
                        style: EvaloopTheme.heading,
                      ),
                      if (message != null) ...[
                        const SizedBox(height: 16),
                        InlineNotice(message),
                      ],
                      const SizedBox(height: 24),
                      const Text(
                        'Email',
                        style: TextStyle(fontWeight: FontWeight.w500),
                      ),
                      const SizedBox(height: 8),
                      TextFormField(
                        key: const ValueKey('login-email'),
                        controller: _email,
                        enabled: !busy,
                        keyboardType: TextInputType.emailAddress,
                        textInputAction: TextInputAction.next,
                        autofillHints: const [AutofillHints.email],
                        decoration: const InputDecoration(),
                        validator: (value) {
                          final text = value?.trim() ?? '';
                          if (text.isEmpty) return 'Masukkan email.';
                          if (text.length > 190 ||
                              !RegExp(r'^\S+@\S+\.\S+$').hasMatch(text)) {
                            return 'Masukkan email yang valid.';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 20),
                      const Text(
                        'Password',
                        style: TextStyle(fontWeight: FontWeight.w500),
                      ),
                      const SizedBox(height: 8),
                      TextFormField(
                        key: const ValueKey('login-password'),
                        controller: _password,
                        enabled: !busy,
                        obscureText: true,
                        enableSuggestions: false,
                        autocorrect: false,
                        textInputAction: TextInputAction.done,
                        autofillHints: const [AutofillHints.password],
                        decoration: const InputDecoration(),
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Masukkan password.';
                          }
                          if (value.length > 128) {
                            return 'Password terlalu panjang.';
                          }
                          return null;
                        },
                        onFieldSubmitted: (_) => _submit(),
                      ),
                      const SizedBox(height: 20),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton(
                          key: const ValueKey('login-submit'),
                          onPressed: busy ? null : _submit,
                          child: Text(busy ? 'Memproses…' : 'Masuk'),
                        ),
                      ),
                      if (widget.onRegister != null) ...[
                        const SizedBox(height: 15),
                        Wrap(
                          crossAxisAlignment: WrapCrossAlignment.center,
                          children: [
                            const Text('Belum punya akun?'),
                            TextButton(
                              onPressed: widget.onRegister,
                              style: TextButton.styleFrom(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 5,
                                ),
                                minimumSize: const Size(44, 44),
                              ),
                              child: const Text(
                                'Daftar',
                                style: TextStyle(
                                  decoration: TextDecoration.underline,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }
}
