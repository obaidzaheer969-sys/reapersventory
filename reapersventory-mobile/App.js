import { StatusBar } from 'expo-status-bar';
import {
  BarCodeScannerBarCodeType,
  Camera,
  CameraType,
  useCameraPermissions,
} from 'expo-camera';
import { useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  BackHandler,
  Platform,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { WebView } from 'react-native-webview';

// Production site. Local Laravel: EXPO_PUBLIC_APP_URL=http://10.0.2.2:8000 (Android emulator)
const APP_URL = process.env.EXPO_PUBLIC_APP_URL || 'https://reapersventory.com';

const BARCODE_TYPES = [
  BarCodeScannerBarCodeType.qr,
  BarCodeScannerBarCodeType.ean13,
  BarCodeScannerBarCodeType.ean8,
  BarCodeScannerBarCodeType.upc_a,
  BarCodeScannerBarCodeType.upc_e,
  BarCodeScannerBarCodeType.code_39,
  BarCodeScannerBarCodeType.code_128,
];

export default function App() {
  const webRef = useRef(null);
  const [canGoBack, setCanGoBack] = useState(false);
  const [isSalePage, setIsSalePage] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [scanning, setScanning] = useState(false);
  const hasScanned = useRef(false);
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();

  // Android back: close camera if scanning, otherwise go back in the WebView.
  useEffect(() => {
    if (Platform.OS !== 'android') return undefined;

    const onBack = () => {
      if (scanning) {
        setScanning(false);
        return true;
      }
      if (canGoBack && webRef.current) {
        webRef.current.goBack();
        return true;
      }
      return false;
    };

    const sub = BackHandler.addEventListener('hardwareBackPress', onBack);
    return () => sub.remove();
  }, [scanning, canGoBack]);

  const handleBarCodeScanned = ({ data }) => {
    if (!data || hasScanned.current) return;
    hasScanned.current = true;
    setScanning(false);
    // Send scanned barcode to the web app so it feeds into the same scanner
    // flow as the USB keyboard scanner (sale form barcodeScan + Enter).
    webRef.current?.postMessage({
      type: 'rv-mobile-barcode',
      code: String(data),
    });
  };

  const openScanner = async () => {
    hasScanned.current = false;
    if (cameraPermission !== 'granted') {
      const { status } = await requestCameraPermission();
      if (status !== 'granted') {
        setError('Camera permission is required to scan barcodes.');
        return;
      }
    }
    setError(null);
    setScanning(true);
  };

  const closeScanner = () => {
    hasScanned.current = false;
    setScanning(false);
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <StatusBar style="light" backgroundColor="#0a1628" />
      {error ? (
        <View style={styles.center}>
          <Text style={styles.errorTitle}>ReapersVentory</Text>
          <Text style={styles.errorText}>{error}</Text>
          <Text
            style={styles.retry}
            onPress={() => {
              setError(null);
              setLoading(true);
              webRef.current?.reload();
            }}
          >
            Tap to retry
          </Text>
        </View>
      ) : (
        <>
          <WebView
            ref={webRef}
            source={{ uri: APP_URL }}
            style={styles.webview}
            onLoadStart={() => setLoading(true)}
            onLoadEnd={() => setLoading(false)}
            onNavigationStateChange={(nav) => {
              setCanGoBack(nav.canGoBack);
              setIsSalePage(
                typeof nav.url === 'string' && nav.url.includes('/sales/create')
              );
            }}
            onError={() => {
              setLoading(false);
              setError('Could not load the app. Check your internet connection.');
            }}
            onHttpError={(e) => {
              if (e.nativeEvent.statusCode >= 500) {
                setError(
                  `Server error (${e.nativeEvent.statusCode}). Try again later.`
                );
              }
            }}
            javaScriptEnabled
            domStorageEnabled
            sharedCookiesEnabled
            thirdPartyCookiesEnabled
            allowsBackForwardNavigationGestures
            setSupportMultipleWindows={false}
            startInLoadingState
          />
          {loading && (
            <View style={styles.loader}>
              <ActivityIndicator size="large" color="#f15a24" />
            </View>
          )}

          {/* Mobile barcode scanner overlay (camera) */}
          {scanning && cameraPermission === 'granted' ? (
            <View style={styles.cameraContainer}>
              <Camera
                style={StyleSheet.absoluteFillObject}
                type={CameraType.back}
                barCodeScannerSettings={{
                  barcodeTypes: BARCODE_TYPES,
                  autoFocus: true,
                }}
                onBarCodeScanned={handleBarCodeScanned}
              />
              <View style={styles.cameraCancel}>
                <TouchableOpacity
                  style={styles.cancelButton}
                  onPress={closeScanner}
                >
                  <Text style={styles.cancelText}>Close</Text>
                </TouchableOpacity>
              </View>
            </View>
          ) : null}

          {/* Floating scan button (only on the billing/sale page) */}
          {isSalePage && !scanning && !loading && !error && (
            <TouchableOpacity style={styles.fab} onPress={openScanner} activeOpacity={0.8}>
              <Text style={styles.fabText}>Scan</Text>
            </TouchableOpacity>
          )}
        </>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: '#0a1628',
  },
  webview: {
    flex: 1,
    backgroundColor: '#0a1628',
  },
  loader: {
    ...StyleSheet.absoluteFillObject,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#0a1628',
  },
  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
    backgroundColor: '#0a1628',
  },
  errorTitle: {
    color: '#f15a24',
    fontSize: 22,
    fontWeight: '700',
    marginBottom: 12,
  },
  errorText: {
    color: '#ffffff',
    textAlign: 'center',
    marginBottom: 16,
    lineHeight: 22,
  },
  retry: {
    color: '#f15a24',
    fontSize: 16,
    fontWeight: '600',
  },
  // Mobile barcode scanner overlay
  cameraContainer: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: '#000',
  },
  cameraCancel: {
    position: 'absolute',
    bottom: 24,
    left: 0,
    right: 0,
    alignItems: 'center',
  },
  cancelButton: {
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 8,
  },
  cancelText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '600',
  },
  // Floating scan button
  fab: {
    position: 'absolute',
    right: 20,
    bottom: 24,
    backgroundColor: '#f15a24',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 26,
    elevation: 6,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
  },
  fabText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '700',
  },
});
