abstract final class EvaloopTime {
  static const _months = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
  ];
  static const _shortMonths = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'Mei',
    'Jun',
    'Jul',
    'Agu',
    'Sep',
    'Okt',
    'Nov',
    'Des',
  ];
  static const _days = [
    'Senin',
    'Selasa',
    'Rabu',
    'Kamis',
    'Jumat',
    'Sabtu',
    'Minggu',
  ];

  static DateTime wita(DateTime instant) =>
      instant.toUtc().add(const Duration(hours: 8));
  static String pad(int value) => value.toString().padLeft(2, '0');
  static String month(DateTime date) => _shortMonths[date.month - 1];
  static String shortDate(DateTime date) =>
      '${date.day} ${month(date)} ${date.year}';
  static String longDate(DateTime date) =>
      '${_days[date.weekday - 1]}, ${date.day} ${_months[date.month - 1]} ${date.year}';
  static String calendarDate(DateTime date) =>
      '${date.day} ${_months[date.month - 1]} ${date.year}';
  static String clock(DateTime date) => '${pad(date.hour)}:${pad(date.minute)}';
  static String parameter(DateTime date) =>
      '${date.year}-${pad(date.month)}-${pad(date.day)}';
  static bool sameDay(DateTime a, DateTime b) =>
      a.year == b.year && a.month == b.month && a.day == b.day;
}
