import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:material_ui/material_ui.dart';

import '../features/auth/auth_api.dart';
import 'evaloop_widgets.dart';

class PrivatePicture extends StatefulWidget {
  const PrivatePicture({
    super.key,
    required this.name,
    required this.load,
    this.fallback,
  });

  final String name;
  final Widget? fallback;
  final Future<ui.Image> Function() load;

  @override
  State<PrivatePicture> createState() => _PrivatePictureState();

  static Future<ui.Image> decode(Future<Uint8List> bytes) async {
    final codec = await ui.instantiateImageCodec(
      await bytes,
      targetWidth: 1200,
      allowUpscaling: false,
    );
    try {
      return (await codec.getNextFrame()).image;
    } finally {
      codec.dispose();
    }
  }
}

class _PrivatePictureState extends State<PrivatePicture> {
  ui.Image? _image;
  ApiFailure? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (_error != null) setState(() => _error = null);
    try {
      final image = await widget.load();
      if (!mounted) {
        image.dispose();
        return;
      }
      setState(() => _image = image);
    } on ApiFailure catch (error) {
      if (mounted) setState(() => _error = error);
    } on Exception {
      if (mounted) {
        setState(() => _error = const ApiFailure('Gambar tidak dapat dibaca.'));
      }
    }
  }

  @override
  void dispose() {
    _image?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_image case final image?) {
      return Semantics(
        image: true,
        label: widget.name,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: AspectRatio(
            aspectRatio: (image.width / image.height)
                .clamp(.75, 2.4)
                .toDouble(),
            // RawImage renders these bytes without using Flutter's shared image cache.
            child: RawImage(image: image, fit: BoxFit.contain),
          ),
        ),
      );
    }
    if (widget.fallback case final fallback?) return fallback;
    if (_error case final error?) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          InlineNotice(error.message),
          if (error.status != 401 && error.status != 403 && error.status != 404)
            TextButton(
              onPressed: _load,
              child: const Text('Muat ulang gambar'),
            ),
        ],
      );
    }
    return const LinearProgressIndicator(semanticsLabel: 'Memuat gambar');
  }
}
