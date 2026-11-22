package id.evaloop.evaloop_mobile

import android.content.ActivityNotFoundException
import android.content.Intent
import android.content.pm.ApplicationInfo
import android.net.Uri
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "id.evaloop/external_links"
        ).setMethodCallHandler { call, result ->
            if (call.method != "openRegistration") {
                result.notImplemented()
                return@setMethodCallHandler
            }

            val uri = Uri.parse(call.arguments as? String ?: "")
            val debuggable = (applicationInfo.flags and ApplicationInfo.FLAG_DEBUGGABLE) != 0
            val localHosts = setOf("10.0.2.2", "127.0.0.1", "localhost", "::1", "[::1]")
            val secureScheme = uri.scheme == "https" ||
                (debuggable && uri.scheme == "http" && uri.host in localHosts)
            if (!secureScheme || uri.host.isNullOrEmpty() || uri.path != "/register" ||
                uri.userInfo != null || uri.query != null || uri.fragment != null
            ) {
                result.error("invalid_url", "Alamat pendaftaran tidak valid.", null)
                return@setMethodCallHandler
            }

            try {
                startActivity(Intent(Intent.ACTION_VIEW, uri))
                result.success(null)
            } catch (_: ActivityNotFoundException) {
                result.error("browser_unavailable", "Browser tidak tersedia.", null)
            }
        }
    }
}
