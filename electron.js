const { app, BrowserWindow, session, ipcMain, protocol } = require('electron');
const { spawn, exec } = require('child_process');
const http = require('http');

const REMOTE_DESKTOP_PROCESSES = [
  '"anydesk.exe"',
  '"teamviewer.exe"',
  '"ultraviewer.exe"',
  '"ultraviewer_desktop.exe"',
  '"rustdesk.exe"',
  '"mstsc.exe"'
];

function checkRemoteDesktopProcesses(callback) {
  if (process.platform !== 'win32') {
    callback(false);
    return;
  }
  
  exec('tasklist /NH /FO CSV', (err, stdout, stderr) => {
    if (err || !stdout) {
      callback(false);
      return;
    }
    
    const running = stdout.toLowerCase();
    const detected = REMOTE_DESKTOP_PROCESSES.some(p => running.includes(p.toLowerCase()));
    const hasRdpSession = process.env.SESSIONNAME && process.env.SESSIONNAME.toLowerCase().startsWith('rdp');
    
    callback(detected || hasRdpSession);
  });
}
const https = require('https');
const os = require('os');
const path = require('path');
const fs = require('fs');

function getSharedDataDir() {
  const appData = process.env.APPDATA
    || (process.platform === 'win32'
      ? path.join(os.homedir(), 'AppData', 'Roaming')
      : path.join(os.homedir(), '.config'));

  return path.join(appData, 'AegisExam', 'data');
}

const DEFAULT_PORT = Number(process.env.AEGIS_PORT || '8000');
let bindHost = '127.0.0.1';
let clientHost = '127.0.0.1';
let isServerHost = true;
let needsServerSetup = false;
const HEALTH_PATH = '/aegis-health.txt';
const SERVER_WAIT_ATTEMPTS = 120;
const SERVER_WAIT_DELAY_MS = 500;
const CLIENT_WAIT_ATTEMPTS = 10;
const CLIENT_WAIT_DELAY_MS = 200;
const LARAVEL_WARMUP_TIMEOUT_MS = 180000;
const HEALTH_CHECK_TIMEOUT_MS = 2000;
const PHP_BINARY_CANDIDATES = [
  path.join(__dirname, 'php', 'php.exe'),
  path.join(process.resourcesPath || __dirname, 'php', 'php.exe'),
  'php',
];
// Izinkan getUserMedia di http://IP-LAN (penting saat mahasiswa pindah device / ganti IP server).
protocol.registerSchemesAsPrivileged([
  {
    scheme: 'http',
    privileges: {
      secure: true,
      standard: true,
      supportFetchAPI: true,
      corsEnabled: true,
      stream: true,
    },
  },
  {
    scheme: 'https',
    privileges: {
      secure: true,
      standard: true,
      supportFetchAPI: true,
      corsEnabled: true,
      stream: true,
    },
  },
]);

const ROLES = {
  dosen: {
    title: 'AegisExam - Desktop Dosen',
    path: '/backend',
    partition: 'persist:aegisexam-dosen',
  },
  mahasiswa: {
    title: 'AegisExam - Desktop Mahasiswa',
    path: '/',
    partition: 'persist:aegisexam-mahasiswa',
  },
  admin: {
    title: 'AegisExam - Desktop Admin',
    path: '/admin',
    partition: 'persist:aegisexam-admin',
  },
};

app.commandLine.appendSwitch('enable-experimental-web-platform-features');
app.commandLine.appendSwitch('enable-features', 'FaceDetection');

ipcMain.on('aegis-set-fullscreen', (event, enabled) => {
  const win = BrowserWindow.fromWebContents(event.sender);

  if (!win) {
    return;
  }

  const shouldEnable = Boolean(enabled);

  win.setMenuBarVisibility(!shouldEnable);

  if (shouldEnable) {
    if (win.isMinimized()) {
      win.restore();
    }

    win.show();
    win.focus();
    win.setFullScreen(true);
    return;
  }

  win.setFullScreen(false);
});

let serverProcess = null;
let tunnelInstance = null;
let port = DEFAULT_PORT;
let baseUrl = `http://127.0.0.1:${port}`;
let lanServerUrl = baseUrl;
let cloudServerUrl = null;
const configuredMediaPartitions = new Set();
const blankReloadCounts = new WeakMap();
const MAX_BLANK_RELOADS = 4;

function loadLocaltunnel() {
  const candidates = [
    path.join(__dirname, 'node_modules', 'localtunnel'),
    path.join(__dirname, '..', 'node_modules', 'localtunnel'),
    path.join(process.resourcesPath || '', 'app', 'node_modules', 'localtunnel'),
  ];

  for (const candidate of candidates) {
    try {
      return require(candidate);
    } catch (error) {
      // try next path
    }
  }

  try {
    return require('localtunnel');
  } catch (error) {
    return null;
  }
}

function httpGet(targetUrl, timeoutMs = 3000) {
  return new Promise((resolve, reject) => {
    const parsed = new URL(targetUrl);
    const lib = parsed.protocol === 'https:' ? https : http;

    const request = lib.get(targetUrl, (response) => {
      response.resume();
      resolve(response);
    });

    request.on('error', reject);
    request.setTimeout(timeoutMs, () => {
      request.destroy();
      reject(new Error('timeout'));
    });
  });
}

function setBaseUrl(url) {
  const normalized = String(url || '').trim().replace(/\/$/, '');
  baseUrl = normalized;

  try {
    const parsed = new URL(normalized);
    clientHost = parsed.hostname;
    port = Number(parsed.port || (parsed.protocol === 'https:' ? 443 : 80));
  } catch (error) {
    clientHost = '127.0.0.1';
    port = DEFAULT_PORT;
  }
}

function normalizeCloudUrl(rawUrl) {
  let value = String(rawUrl || '').trim();

  if (!value) {
    return null;
  }

  if (!/^https?:\/\//i.test(value)) {
    value = `https://${value}`;
  }

  const parsed = new URL(value);

  return parsed.origin;
}

function getServerConfigPath() {
  return path.join(path.dirname(getSharedDataDir()), 'server.json');
}

function readServerConfig() {
  const configPath = getServerConfigPath();

  if (!fs.existsSync(configPath)) {
    return {};
  }

  try {
    return JSON.parse(fs.readFileSync(configPath, 'utf8')) || {};
  } catch (error) {
    return {};
  }
}

function buildInsecureOriginList() {
  const origins = new Set();
  const config = readServerConfig();

  for (let candidatePort = DEFAULT_PORT; candidatePort < DEFAULT_PORT + 20; candidatePort += 1) {
    origins.add(`http://127.0.0.1:${candidatePort}`);
    origins.add(`http://localhost:${candidatePort}`);

    if (config.host && config.host !== '127.0.0.1') {
      origins.add(`http://${config.host}:${config.port || candidatePort}`);
    }
  }

  for (const key of ['cloudUrl', 'lanUrl', 'localUrl']) {
    if (!config[key]) {
      continue;
    }

    try {
      origins.add(new URL(config[key]).origin);
    } catch (error) {
      // ignore invalid saved URL
    }
  }

  return [...origins].join(',');
}

app.commandLine.appendSwitch(
  'unsafely-treat-insecure-origin-as-secure',
  buildInsecureOriginList(),
);

function writeServerConfig(config) {
  const configPath = getServerConfigPath();
  fs.mkdirSync(path.dirname(configPath), { recursive: true });
  fs.writeFileSync(configPath, JSON.stringify(config, null, 2));
}

function getLocalLanIPv4() {
  const interfaces = os.networkInterfaces();

  for (const entries of Object.values(interfaces)) {
    for (const entry of entries || []) {
      if (entry.family === 'IPv4' && !entry.internal) {
        return entry.address;
      }
    }
  }

  return '127.0.0.1';
}

function applyLocalBaseUrl() {
  setBaseUrl(`http://${clientHost}:${port}`);
}

function initNetworking(role) {
  const config = readServerConfig();

  if (role === 'dosen' || role === 'admin') {
    isServerHost = true;
    needsServerSetup = false;
    bindHost = '0.0.0.0';
    clientHost = '127.0.0.1';
    port = Number(config.port || DEFAULT_PORT);
    applyLocalBaseUrl();
    return;
  }

  isServerHost = false;
  bindHost = '127.0.0.1';

  if (config.cloudUrl) {
    needsServerSetup = false;
    setBaseUrl(config.cloudUrl);
    return;
  }

  if (config.host) {
    needsServerSetup = false;
    clientHost = config.host;
    port = Number(config.port || DEFAULT_PORT);
    applyLocalBaseUrl();
    return;
  }

  needsServerSetup = true;
}

async function stopCloudTunnel() {
  if (tunnelInstance) {
    tunnelInstance.close();
    tunnelInstance = null;
  }

  cloudServerUrl = null;
}

async function startCloudTunnel() {
  const localtunnel = loadLocaltunnel();

  if (!localtunnel) {
    return null;
  }

  await stopCloudTunnel();

  try {
    tunnelInstance = await localtunnel({ port, local_host: '127.0.0.1' });
    cloudServerUrl = tunnelInstance.url.replace(/\/$/, '');

    tunnelInstance.on('close', () => {
      cloudServerUrl = null;
      tunnelInstance = null;
    });

    return cloudServerUrl;
  } catch (error) {
    console.warn('Mode cloud (tunnel) gagal:', error.message);
    return null;
  }
}

function persistHostServerConfig() {
  const lanIp = getLocalLanIPv4();
  lanServerUrl = `http://${lanIp}:${port}`;

  writeServerConfig({
    mode: 'host',
    host: lanIp,
    port,
    lanUrl: lanServerUrl,
    cloudUrl: cloudServerUrl,
    localUrl: `http://127.0.0.1:${port}`,
    updatedAt: new Date().toISOString(),
  });
}

ipcMain.handle('aegis-save-server-connection', async (_event, payload) => {
  const connectionMode = payload?.connectionMode === 'cloud' ? 'cloud' : 'lan';

  if (connectionMode === 'cloud') {
    const cloudUrl = normalizeCloudUrl(payload?.cloudUrl);

    if (!cloudUrl) {
      return { ok: false, message: 'URL cloud wajib diisi (contoh: https://xxxx.loca.lt).' };
    }

    setBaseUrl(cloudUrl);
    isServerHost = false;
    needsServerSetup = false;

    writeServerConfig({
      mode: 'client',
      connectionMode: 'cloud',
      cloudUrl: baseUrl,
      updatedAt: new Date().toISOString(),
    });

    await refreshMahasiswaClientWindows(_event.sender);
    relaunchMahasiswaForCamera();
    return { ok: true, baseUrl, restarting: true };
  }

  const host = String(payload?.host || '').trim();
  const nextPort = Number(payload?.port || DEFAULT_PORT);

  if (!host) {
    return { ok: false, message: 'Alamat IP server wajib diisi.' };
  }

  clientHost = host;
  port = nextPort;
  isServerHost = false;
  needsServerSetup = false;
  applyLocalBaseUrl();

  writeServerConfig({
    mode: 'client',
    connectionMode: 'lan',
    host: clientHost,
    port,
    updatedAt: new Date().toISOString(),
  });

  await refreshMahasiswaClientWindows(_event.sender);
  relaunchMahasiswaForCamera();
  return { ok: true, baseUrl, restarting: true };
});

function relaunchMahasiswaForCamera() {
  if (getRole() !== 'mahasiswa') {
    return;
  }

  setImmediate(() => {
    app.relaunch();
    app.exit(0);
  });
}

ipcMain.handle('aegis-open-server-setup', async () => {
  await showServerSetupWindow({ required: false });
  return { ok: true };
});

ipcMain.handle('aegis-retry-server-connection', async (event) => {
  if (!(await checkPhpServer(3000))) {
    return {
      ok: false,
      message: 'Server dosen belum bisa dihubungi. Buka AegisExam Dosen di PC dosen, lalu coba lagi.',
    };
  }

  const win = BrowserWindow.fromWebContents(event.sender);

  if (win) {
    await navigateWindowToApp(win, 'mahasiswa');
  }

  return { ok: true, baseUrl };
});

ipcMain.handle('aegis-reset-server-connection', async (_event) => {
  const configPath = getServerConfigPath();

  if (fs.existsSync(configPath)) {
    fs.unlinkSync(configPath);
  }

  needsServerSetup = true;
  await refreshMahasiswaClientWindows(_event.sender);
  return { ok: true };
});

ipcMain.handle('aegis-get-server-info', async () => {
  const config = readServerConfig();

  return {
    isServerHost,
    baseUrl,
    lanUrl: config.lanUrl || lanServerUrl,
    cloudUrl: config.cloudUrl || cloudServerUrl,
    localUrl: config.localUrl || `http://127.0.0.1:${port}`,
    host: config.host || getLocalLanIPv4(),
    port,
  };
});

function getPhpBinary() {
  if (app.isPackaged) {
    const bundledPhp = path.join(__dirname, 'php', 'php.exe');

    if (!fs.existsSync(bundledPhp)) {
      throw new Error('PHP portable tidak ditemukan di paket aplikasi.');
    }

    return bundledPhp;
  }

  const bundledPhp = PHP_BINARY_CANDIDATES.find((candidate) => candidate === 'php' || fs.existsSync(candidate));
  return bundledPhp || 'php';
}

function getRole() {
  const roleArg = process.argv.find((arg) => arg.startsWith('--role='));
  const role = roleArg ? roleArg.split('=')[1] : 'mahasiswa';

  return ['dosen', 'mahasiswa', 'admin', 'all'].includes(role) ? role : 'mahasiswa';
}

function checkPhpServer(timeoutMs = HEALTH_CHECK_TIMEOUT_MS) {
  return httpGet(`${baseUrl}${HEALTH_PATH}`, timeoutMs)
    .then((response) => response.statusCode === 200)
    .catch(() => false);
}

function checkServer() {
  return checkPhpServer();
}

function warmupLaravel(requestPath) {
  return httpGet(`${baseUrl}${requestPath}`, LARAVEL_WARMUP_TIMEOUT_MS)
    .then((response) => response.statusCode)
    .catch((error) => {
      throw new Error(error.message === 'timeout'
        ? 'Laravel masih memuat terlalu lama. Coba tutup aplikasi lalu buka lagi.'
        : error.message);
    });
}

function isTrustedLocalUrl(rawUrl) {
  try {
    return new URL(rawUrl).origin === new URL(baseUrl).origin;
  } catch (error) {
    return false;
  }
}

async function refreshMahasiswaClientWindows(senderWebContents) {
  initNetworking('mahasiswa');

  const senderWin = senderWebContents
    ? BrowserWindow.fromWebContents(senderWebContents)
    : null;

  if (senderWin && (senderWin.getTitle() || '').includes('Hubungkan Server')) {
    senderWin.close();
  }

  for (const win of BrowserWindow.getAllWindows()) {
    const title = win.getTitle() || '';

    if (!title.includes('Mahasiswa')) {
      continue;
    }

    await loadMahasiswaClientPage(win);
  }
}

function isPortAvailable(candidatePort) {
  return new Promise((resolve) => {
    const server = http.createServer();

    server.once('error', () => resolve(false));
    server.once('listening', () => {
      server.close(() => resolve(true));
    });
    server.listen(candidatePort, bindHost);
  });
}

async function choosePort() {
  for (let candidatePort = DEFAULT_PORT; candidatePort < DEFAULT_PORT + 20; candidatePort += 1) {
    if (await isPortAvailable(candidatePort)) {
      port = candidatePort;
      applyLocalBaseUrl();
      return;
    }
  }

  throw new Error(`Tidak ada port kosong dari ${DEFAULT_PORT} sampai ${DEFAULT_PORT + 19}`);
}

function configureMediaPermissions(partition) {
  if (configuredMediaPartitions.has(partition)) {
    return;
  }

  const ses = session.fromPartition(partition);

  ses.setPermissionRequestHandler((webContents, permission, callback, details) => {
    if (permission !== 'media') {
      callback(false);
      return;
    }

    const mediaTypes = details.mediaTypes || [];
    const wantsCamera = mediaTypes.length === 0 || mediaTypes.includes('video');
    const wantsMicrophone = mediaTypes.includes('audio');
    const requestingUrl = details.requestingUrl || webContents.getURL();

    callback(isTrustedLocalUrl(requestingUrl) && wantsCamera && !wantsMicrophone);
  });

  ses.setPermissionCheckHandler((webContents, permission, requestingOrigin) => {
    if (permission !== 'media') {
      return false;
    }

    const requestingUrl = requestingOrigin || webContents.getURL();
    return isTrustedLocalUrl(requestingUrl);
  });

  ses.webRequest.onBeforeSendHeaders((details, callback) => {
    details.requestHeaders['bypass-tunnel-reminder'] = 'true';
    callback({ requestHeaders: details.requestHeaders });
  });

  configuredMediaPartitions.add(partition);
}

function startLaravelServer() {
  const routerPath = path.join(__dirname, 'electron-server.php');

  const logPath = path.join(__dirname, 'storage', 'logs', 'electron-php-server.log');
  const sharedDataDir = getSharedDataDir();
  const sqlitePath = path.join(__dirname, 'database', 'database.sqlite');
  fs.mkdirSync(path.dirname(logPath), { recursive: true });
  fs.mkdirSync(sharedDataDir, { recursive: true });
  fs.mkdirSync(path.dirname(sqlitePath), { recursive: true });
  if (!fs.existsSync(sqlitePath)) {
    fs.writeFileSync(sqlitePath, '');
  }
  const logStream = fs.createWriteStream(logPath, { flags: 'a' });

  serverProcess = spawn(
    getPhpBinary(),
    [
      '-d', 'max_execution_time=300',
      '-d', 'memory_limit=256M',
      '-S', `${bindHost}:${port}`,
      '-t', path.join(__dirname, 'public'),
      routerPath,
    ],
    {
      cwd: __dirname,
      env: {
        ...process.env,
        PHPRC: __dirname,
        AEGIS_DESKTOP: '1',
        AEGIS_SHARED_DATA: sharedDataDir,
        AEGIS_LAN_URL: lanServerUrl,
        AEGIS_CLOUD_URL: cloudServerUrl || '',
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: sqlitePath,
      },
      shell: false,
      windowsHide: true,
      stdio: ['ignore', 'pipe', 'pipe'],
    },
  );

  serverProcess.stdout.on('data', (data) => {
    logStream.write(data);
  });

  serverProcess.stderr.on('data', (data) => {
    logStream.write(data);
  });

  serverProcess.on('exit', (code) => {
    logStream.write(`\n[electron] PHP server exited with code ${code}\n`);
    logStream.end();
    serverProcess = null;
  });

  serverProcess.on('error', (error) => {
    logStream.write(`\n[electron] PHP server error: ${error.message}\n`);
  });
}

async function waitForRemoteServer({ fast = false } = {}) {
  const maxAttempts = fast ? CLIENT_WAIT_ATTEMPTS : SERVER_WAIT_ATTEMPTS;
  const delayMs = fast ? CLIENT_WAIT_DELAY_MS : SERVER_WAIT_DELAY_MS;

  if (await checkPhpServer()) {
    return;
  }

  for (let attempt = 1; attempt < maxAttempts; attempt += 1) {
    await new Promise((resolve) => setTimeout(resolve, delayMs));

    if (await checkPhpServer()) {
      return;
    }
  }

  throw new Error(
    `Tidak bisa terhubung ke server ${baseUrl}. Pastikan AegisExam Dosen sudah dibuka. Mode LAN: satu WiFi. Mode cloud: paste URL dari dashboard dosen.`,
  );
}

function getMahasiswaOfflineHtml() {
  const config = readServerConfig();
  const target = baseUrl || 'belum diatur';
  const warnLocalhost = config.host === '127.0.0.1'
    ? '<p style="margin-top:10px;padding:10px;border-radius:10px;background:#fef2f2;color:#b91c1c;font-size:13px;"><strong>IP 127.0.0.1 salah untuk device berbeda.</strong> Isi IP WiFi dari dashboard dosen, atau pakai tab Cloud.</p>'
    : '';

  return `
    <main style="font-family: Arial, sans-serif; max-width: 560px; margin: 32px auto; padding: 24px;">
      <h1 style="margin: 0 0 8px;">Menunggu Server Dosen</h1>
      <p style="color: #475569; line-height: 1.5;">
        PC dosen dan mahasiswa <strong>beda device</strong>. Dosen harus buka <strong>AegisExam Dosen</strong> dulu.
        Lalu isi IP/URL di bawah (dari dashboard dosen).
      </p>
      <p style="margin-top:10px;padding:10px;border-radius:10px;background:#f1f5f9;color:#334155;font-size:13px;">
        Target sekarang: <code>${target}</code> — belum bisa dihubungi.
      </p>
      ${warnLocalhost}
      <div style="display:flex; gap:8px; margin-top:16px;">
        <button type="button" id="tab-lan" style="flex:1; padding:10px; border-radius:10px; border:1px solid #cbd5e1; background:#0f766e; color:#fff; font-weight:700; cursor:pointer;">LAN (WiFi sama)</button>
        <button type="button" id="tab-cloud" style="flex:1; padding:10px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-weight:700; cursor:pointer;">Cloud (Internet)</button>
      </div>
      <div id="panel-lan" style="margin-top:16px;">
        <label style="display:block; font-size: 12px; font-weight: 700; color: #64748b;">IP SERVER (dari dashboard dosen)</label>
        <input id="server-host" type="text" placeholder="192.168.1.10" value="${config.host || ''}" style="margin-top: 8px; width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 12px;" />
        <label style="display:block; margin-top: 12px; font-size: 12px; font-weight: 700; color: #64748b;">PORT</label>
        <input id="server-port" type="number" value="${config.port || DEFAULT_PORT}" style="margin-top: 8px; width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 12px;" />
      </div>
      <div id="panel-cloud" style="display:none; margin-top:16px;">
        <label style="display:block; font-size: 12px; font-weight: 700; color: #64748b;">URL CLOUD (dari dashboard dosen)</label>
        <input id="server-cloud-url" type="text" placeholder="https://xxxx.loca.lt" value="${config.cloudUrl || ''}" style="margin-top: 8px; width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 12px;" />
        <p style="margin-top:8px; font-size:12px; color:#64748b;">Untuk device/jaringan berbeda (rumah ↔ kampus).</p>
      </div>
      <p id="setup-error" style="display:none; margin-top: 12px; color: #b91c1c; font-size: 14px;"></p>
      <button id="btn-connect-server" type="button" style="margin-top: 12px; width: 100%; padding: 14px; border: 0; border-radius: 12px; background: #0f766e; color: white; font-weight: 700; cursor: pointer;">Simpan koneksi</button>
      <button id="btn-retry-server" type="button" style="margin-top: 10px; width: 100%; padding: 14px; border: 1px solid #cbd5e1; border-radius: 12px; background: #fff; color: #334155; font-weight: 700; cursor: pointer;">Coba sambungkan lagi (dosen sudah dibuka)</button>
      <script>
        let mode = '${config.cloudUrl ? 'cloud' : 'lan'}';
        const tabLan = document.getElementById('tab-lan');
        const tabCloud = document.getElementById('tab-cloud');
        const panelLan = document.getElementById('panel-lan');
        const panelCloud = document.getElementById('panel-cloud');
        const err = document.getElementById('setup-error');
        function setMode(next) {
          mode = next;
          const lanActive = mode === 'lan';
          tabLan.style.background = lanActive ? '#0f766e' : '#fff';
          tabLan.style.color = lanActive ? '#fff' : '#334155';
          tabCloud.style.background = !lanActive ? '#0f766e' : '#fff';
          tabCloud.style.color = !lanActive ? '#fff' : '#334155';
          panelLan.style.display = lanActive ? 'block' : 'none';
          panelCloud.style.display = lanActive ? 'none' : 'block';
        }
        setMode(mode);
        tabLan.addEventListener('click', () => setMode('lan'));
        tabCloud.addEventListener('click', () => setMode('cloud'));
        document.getElementById('btn-connect-server').addEventListener('click', async () => {
          const btn = document.getElementById('btn-connect-server');
          err.style.display = 'none';
          btn.disabled = true;
          btn.textContent = 'Menyimpan...';
          try {
            let result;
            if (mode === 'cloud') {
              result = await window.aegisDesktop.saveServerConnection({
                connectionMode: 'cloud',
                cloudUrl: document.getElementById('server-cloud-url').value.trim(),
              });
            } else {
              const host = document.getElementById('server-host').value.trim();
              const port = Number(document.getElementById('server-port').value || ${DEFAULT_PORT});
              if (!host) {
                err.style.display = 'block';
                err.textContent = 'IP server wajib diisi.';
                btn.disabled = false;
                btn.textContent = 'Simpan koneksi';
                return;
              }
              result = await window.aegisDesktop.saveServerConnection({ connectionMode: 'lan', host, port });
            }
            if (!result?.ok) {
              err.style.display = 'block';
              err.textContent = result.message || 'Gagal menyimpan.';
              btn.disabled = false;
              btn.textContent = 'Simpan koneksi';
              return;
            }
            btn.textContent = 'Tersimpan, memuat ulang...';
            setTimeout(() => {
              btn.disabled = false;
              btn.textContent = 'Simpan koneksi';
            }, 4000);
          } catch (e) {
            err.style.display = 'block';
            err.textContent = 'Gagal menyimpan koneksi.';
            btn.disabled = false;
            btn.textContent = 'Simpan koneksi';
          }
        });
        document.getElementById('btn-retry-server').addEventListener('click', async () => {
          const retryBtn = document.getElementById('btn-retry-server');
          retryBtn.disabled = true;
          retryBtn.textContent = 'Menghubungkan...';
          try {
            const result = await window.aegisDesktop.retryServerConnection();
            if (!result?.ok) {
              err.style.display = 'block';
              err.textContent = result.message || 'Masih belum terhubung.';
              retryBtn.disabled = false;
              retryBtn.textContent = 'Coba sambungkan lagi (dosen sudah dibuka)';
            }
          } catch (e) {
            err.style.display = 'block';
            err.textContent = 'Gagal menghubungi server.';
            retryBtn.disabled = false;
            retryBtn.textContent = 'Coba sambungkan lagi (dosen sudah dibuka)';
          }
        });
      </script>
    </main>
  `;
}

function getServerSetupHtml() {
  return `
    <main style="font-family: Arial, sans-serif; max-width: 560px; margin: 32px auto; padding: 24px;">
      <h1 style="margin: 0 0 8px;">Hubungkan ke Server Ujian</h1>
      <p style="color: #475569; line-height: 1.5;">Pilih mode koneksi. Device dosen beda → pakai <strong>Cloud</strong> atau IP WiFi dosen (LAN).</p>
      <div style="display:flex; gap:8px; margin-top:16px;">
        <button type="button" id="tab-lan" style="flex:1; padding:10px; border-radius:10px; border:1px solid #cbd5e1; background:#0f766e; color:#fff; font-weight:700; cursor:pointer;">LAN (WiFi sama)</button>
        <button type="button" id="tab-cloud" style="flex:1; padding:10px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-weight:700; cursor:pointer;">Cloud (Internet)</button>
      </div>
      <div id="panel-lan" style="margin-top:16px;">
        <label style="display:block; font-size: 12px; font-weight: 700; color: #64748b;">IP SERVER (dari dashboard dosen)</label>
        <input id="server-host" type="text" placeholder="192.168.1.10" style="margin-top: 8px; width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 12px;" />
        <label style="display:block; margin-top: 12px; font-size: 12px; font-weight: 700; color: #64748b;">PORT</label>
        <input id="server-port" type="number" value="${DEFAULT_PORT}" style="margin-top: 8px; width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 12px;" />
      </div>
      <div id="panel-cloud" style="display:none; margin-top:16px;">
        <label style="display:block; font-size: 12px; font-weight: 700; color: #64748b;">URL CLOUD (dari dashboard dosen)</label>
        <input id="server-cloud-url" type="text" placeholder="https://xxxx.loca.lt" style="margin-top: 8px; width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 12px;" />
        <p style="margin-top:8px; font-size:12px; color:#64748b;">Dosen harus buka aplikasi dulu. Internet wajib aktif di kedua device.</p>
      </div>
      <p id="setup-error" style="display:none; margin-top: 12px; color: #b91c1c; font-size: 14px;"></p>
      <button id="btn-connect-server" type="button" style="margin-top: 20px; width: 100%; padding: 14px; border: 0; border-radius: 12px; background: #0f766e; color: white; font-weight: 700; cursor: pointer;">Sambungkan</button>
      <p style="margin-top: 12px; font-size: 12px; color: #64748b;">Setelah tersambung, aplikasi restart otomatis agar kamera LAN aktif.</p>
      <script>
        let mode = 'lan';
        const tabLan = document.getElementById('tab-lan');
        const tabCloud = document.getElementById('tab-cloud');
        const panelLan = document.getElementById('panel-lan');
        const panelCloud = document.getElementById('panel-cloud');
        const btn = document.getElementById('btn-connect-server');
        const err = document.getElementById('setup-error');
        function setMode(next) {
          mode = next;
          const lanActive = mode === 'lan';
          tabLan.style.background = lanActive ? '#0f766e' : '#fff';
          tabLan.style.color = lanActive ? '#fff' : '#334155';
          tabCloud.style.background = !lanActive ? '#0f766e' : '#fff';
          tabCloud.style.color = !lanActive ? '#fff' : '#334155';
          panelLan.style.display = lanActive ? 'block' : 'none';
          panelCloud.style.display = lanActive ? 'none' : 'block';
        }
        tabLan.addEventListener('click', () => setMode('lan'));
        tabCloud.addEventListener('click', () => setMode('cloud'));
        btn.addEventListener('click', async () => {
          err.style.display = 'none';
          btn.disabled = true;
          btn.textContent = 'Menyambungkan...';
          try {
            let result;
            if (mode === 'cloud') {
              result = await window.aegisDesktop.saveServerConnection({
                connectionMode: 'cloud',
                cloudUrl: document.getElementById('server-cloud-url').value.trim(),
              });
            } else {
              const host = document.getElementById('server-host').value.trim();
              const port = Number(document.getElementById('server-port').value || ${DEFAULT_PORT});
              if (!host) {
                err.style.display = 'block';
                err.textContent = 'IP server wajib diisi.';
                btn.disabled = false;
                btn.textContent = 'Sambungkan';
                return;
              }
              result = await window.aegisDesktop.saveServerConnection({ connectionMode: 'lan', host, port });
            }
            if (!result.ok) {
              err.style.display = 'block';
              err.textContent = result.message || 'Gagal menyimpan koneksi.';
              btn.disabled = false;
              btn.textContent = 'Sambungkan';
              return;
            }
            btn.textContent = 'Tersimpan!';
            window.close();
          } catch (e) {
            err.style.display = 'block';
            err.textContent = 'Gagal menyambungkan. Pastikan dosen sudah buka aplikasi.';
            btn.disabled = false;
            btn.textContent = 'Sambungkan';
          }
        });
      </script>
    </main>
  `;
}

async function showServerSetupWindow({ required = false } = {}) {
  return new Promise((resolve, reject) => {
    const setupWin = new BrowserWindow({
      width: 640,
      height: 560,
      title: 'AegisExam - Hubungkan Server',
      icon: path.join(__dirname, 'public', 'aegis_logo.png'),
      resizable: false,
      webPreferences: {
        preload: path.join(__dirname, 'preload.js'),
        nodeIntegration: false,
        contextIsolation: true,
      },
    });

    setupWin.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(getServerSetupHtml())}`);

    setupWin.on('closed', () => {
      if (!required || !needsServerSetup) {
        resolve();
        return;
      }

      reject(new Error('Koneksi server dibatalkan.'));
    });
  });
}

async function waitForServer(role) {
  if (needsServerSetup) {
    await showServerSetupWindow({ required: true });
    await waitForRemoteServer();
    return;
  }

  if (!isServerHost) {
    const fast = role === 'mahasiswa';

    try {
      await waitForRemoteServer({ fast });
    } catch (error) {
      if (role === 'mahasiswa') {
        return;
      }

      throw error;
    }
    return;
  }

  if (!app.isPackaged && (await checkServer())) {
    persistHostServerConfig();
    return;
  }

  await choosePort();
  lanServerUrl = `http://${getLocalLanIPv4()}:${port}`;
  persistHostServerConfig();
  startLaravelServer();

  cloudServerUrl = await startCloudTunnel();
  persistHostServerConfig();

  for (let attempt = 0; attempt < SERVER_WAIT_ATTEMPTS; attempt += 1) {
    await new Promise((resolve) => setTimeout(resolve, SERVER_WAIT_DELAY_MS));

    if (await checkPhpServer()) {
      return;
    }

    if (serverProcess && serverProcess.exitCode !== null) {
      throw new Error('Proses PHP server berhenti. Buka storage/logs/electron-php-server.log untuk detail.');
    }
  }

  throw new Error(`PHP server tidak merespons di ${baseUrl}${HEALTH_PATH}`);
}

function getLoadingHtml(message, hint) {
  return `
    <main style="font-family: Arial, sans-serif; padding: 48px; text-align: center;">
      <h1>${message}</h1>
      <p>${hint || 'Mohon tunggu sebentar...'}</p>
    </main>
  `;
}

function createBrowserWindowForRole(roleName) {
  const role = ROLES[roleName];
  configureMediaPermissions(role.partition);

  const win = new BrowserWindow({
    width: 1200,
    height: 850,
    show: false,
    title: role.title,
    icon: path.join(__dirname, 'public', 'aegis_logo.png'),
    autoHideMenuBar: roleName === 'mahasiswa',
    backgroundColor: '#f4f7fb',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
      partition: role.partition,
    },
  });

  win.once('ready-to-show', () => {
    win.show();
  });

  attachWebPageHealth(win);

  return { win, role };
}

function getBlankPageCheckScript() {
  return `(function () {
    const text = (document.body && document.body.innerText || '').replace(/\\s+/g, ' ').trim();
    const hasMain = !!document.querySelector(
      'form, h1, main, .card, .wrap, #server-connection-panel, #pre-exam-screen, #exam-interface',
    );
    const bg = window.getComputedStyle(document.body).backgroundColor;
    const darkBg = bg === 'rgb(0, 0, 0)'
      || bg === 'rgb(3, 7, 18)'
      || bg === 'rgb(9, 9, 11)'
      || bg === 'rgb(24, 24, 27)';
    const blank = (text.length < 12 && !hasMain) || (darkBg && text.length < 28);
    return { blank, textLength: text.length, hasMain, darkBg };
  })();`;
}

function applyWebPageThemeFixes(win, currentUrl) {
  if (currentUrl.startsWith('data:')) {
    return Promise.resolve();
  }

  const isExamTakePage = /\/exam\/?$/.test(new URL(currentUrl).pathname || '');

  return win.webContents.executeJavaScript(`
    (function () {
      document.documentElement.style.backgroundColor = '#f4f7fb';
      document.body.style.backgroundColor = '#f4f7fb';
      document.body.style.color = '#0f172a';

      const keepFullscreen = window.aegisExamActive === true
        || (function () {
          const el = document.getElementById('exam-interface');
          return el && !el.classList.contains('hidden') && el.style.display !== 'none';
        })();

      const isExamPath = ${isExamTakePage ? 'true' : 'false'};
      if (keepFullscreen || isExamPath) {
        return;
      }

      if (window.aegisDesktop?.setFullScreen) {
        window.aegisDesktop.setFullScreen(false);
      }
      if (document.fullscreenElement && document.exitFullscreen) {
        document.exitFullscreen().catch(function () {});
      }
    })();
  `).catch(() => {});
}

async function autoReloadIfBlankPage(win) {
  const currentUrl = win.webContents.getURL();

  if (currentUrl.startsWith('data:')) {
    return;
  }

  await new Promise((resolve) => setTimeout(resolve, 1200));

  let health;

  try {
    health = await win.webContents.executeJavaScript(getBlankPageCheckScript());
  } catch (error) {
    return;
  }

  if (!health.blank) {
    blankReloadCounts.delete(win);
    return;
  }

  const reloadCount = blankReloadCounts.get(win) || 0;

  if (reloadCount >= MAX_BLANK_RELOADS) {
    return;
  }

  blankReloadCounts.set(win, reloadCount + 1);
  win.webContents.reload();
}

function attachWebPageHealth(win) {
  if (win.aegisHealthAttached) {
    return;
  }

  win.aegisHealthAttached = true;

  win.webContents.on('did-finish-load', () => {
    const currentUrl = win.webContents.getURL();

    applyWebPageThemeFixes(win, currentUrl).finally(() => {
      autoReloadIfBlankPage(win);
    });
  });
}

function showLoadingInWindow(win, roleName) {
  const role = ROLES[roleName];
  const hint = roleName === 'mahasiswa'
    ? 'Menghubungkan ke server dosen...'
    : 'Memuat server ujian (pertama kali bisa 1-2 menit)...';

  win.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(getLoadingHtml(role.title, hint))}`);
}

async function loadMahasiswaClientPage(win) {
  if (!(await checkPhpServer(1500))) {
    win.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(getMahasiswaOfflineHtml())}`);
    return;
  }

  await navigateWindowToApp(win, 'mahasiswa');
}

async function navigateWindowToApp(win, roleName) {
  const role = ROLES[roleName];
  const skipWarmup = roleName === 'mahasiswa' && !isServerHost;

  if (app.isPackaged && !skipWarmup) {
    try {
      await warmupLaravel(role.path);
    } catch (error) {
      win.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(`
        <main style="font-family: Arial, sans-serif; padding: 32px;">
          <h1>${role.title}</h1>
          <p>${error.message}</p>
          <p>Cek <code>storage/logs/laravel.log</code> bila masih gagal.</p>
        </main>
      `)}`);
      return;
    }
  }

  win.loadURL(`${baseUrl}${role.path}`);
}

async function createWindow(roleName) {
  const { win } = createBrowserWindowForRole(roleName);

  if (app.isPackaged) {
    showLoadingInWindow(win, roleName);
  }

  await navigateWindowToApp(win, roleName);
}

async function startMahasiswaApp() {
  if (needsServerSetup) {
    await showServerSetupWindow({ required: true });
  }

  const { win } = createBrowserWindowForRole('mahasiswa');
  showLoadingInWindow(win, 'mahasiswa');
  await loadMahasiswaClientPage(win);
}
app.whenReady().then(async () => {
  const role = getRole();

  initNetworking(role);

  if (role === 'mahasiswa' || role === 'all') {
    setInterval(() => {
      checkRemoteDesktopProcesses((detected) => {
        if (detected) {
          BrowserWindow.getAllWindows().forEach((win) => {
            if (!win.isDestroyed()) {
              win.webContents.send('aegis-remote-desktop-detected', true);
            }
          });
        }
      });
    }, 3000);
  }

  try {
    if (role === 'mahasiswa') {
      await startMahasiswaApp();
      return;
    }

    await waitForServer(role);

    if (role === 'all') {
      createWindow('dosen');
      createWindow('mahasiswa');
      return;
    }

    createWindow(role);
  } catch (error) {
    const win = new BrowserWindow({ width: 720, height: 420, icon: path.join(__dirname, 'public', 'aegis_logo.png') });
    win.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(`
      <main style="font-family: Arial, sans-serif; padding: 32px;">
        <h1>AegisExam Desktop</h1>
        <p>${error.message}</p>
        <p>Pastikan folder <code>php</code> ada di paket aplikasi, lalu jalankan ulang.</p>
        <p>Pertama kali buka bisa memakan waktu 1-2 menit.</p>
      </main>
    `)}`);
  }
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    stopCloudTunnel();
    if (serverProcess) {
      serverProcess.kill();
    }

    app.quit();
  }
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) {
    const role = getRole();
    createWindow(role === 'all' ? 'mahasiswa' : role);
  }
});
