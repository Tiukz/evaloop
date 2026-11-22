import 'package:material_ui/material_ui.dart';

abstract final class EvaloopTheme {
  static const canvas = Color(0xFFF7FAFB);
  static const ice = Color(0xFFF0F8FA);
  static const mist = Color(0xFFE3F2F5);
  static const aqua = Color(0xFFB9E6E5);
  static const ink = Color(0xFF253344);
  static const muted = Color(0xFF526476);
  static const teal = Color(0xFF0F766E);
  static const tealSoft = Color(0xFFE4F5F1);
  static const border = Color(0xFFD9E5E8);
  static const formBorder = Color(0xFFCBD5E1);
  static const danger = Color(0xFFB42318);
  static const dangerSoft = Color(0xFFFEF0EE);
  static const editorial = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [mist, ice, Colors.white],
    stops: [0, .55, 1],
  );

  static const heading = TextStyle(
    fontFamily: 'Geist',
    fontSize: 28,
    height: 1.3,
    letterSpacing: -.56,
    fontWeight: FontWeight.w600,
    color: ink,
  );

  static ThemeData get light {
    OutlineInputBorder outline(Color color) => OutlineInputBorder(
      borderRadius: BorderRadius.circular(8),
      borderSide: BorderSide(color: color),
    );

    return ThemeData(
      useMaterial3: true,
      fontFamily: 'Inter',
      scaffoldBackgroundColor: canvas,
      colorScheme: ColorScheme.fromSeed(
        seedColor: teal,
        surface: Colors.white,
        onSurface: ink,
        onSurfaceVariant: muted,
        primary: teal,
        onPrimary: Colors.white,
        error: danger,
      ),
      textTheme: const TextTheme(
        headlineMedium: heading,
        titleLarge: TextStyle(
          fontFamily: 'Geist',
          fontSize: 18,
          fontWeight: FontWeight.w600,
          height: 1.35,
        ),
        bodyMedium: TextStyle(fontSize: 14, height: 1.5),
        labelLarge: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(0, 44),
          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        isDense: true,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 12,
          vertical: 11,
        ),
        enabledBorder: outline(formBorder),
        focusedBorder: outline(teal),
        errorBorder: outline(danger),
        focusedErrorBorder: outline(danger),
        disabledBorder: outline(border),
      ),
    );
  }
}
