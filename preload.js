const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('aegisDesktop', {
  isElectron: true,
  setFullScreen: (enabled) => ipcRenderer.send('aegis-set-fullscreen', Boolean(enabled)),
  saveServerConnection: (payload) => ipcRenderer.invoke('aegis-save-server-connection', payload),
  resetServerConnection: () => ipcRenderer.invoke('aegis-reset-server-connection'),
  openServerSetup: () => ipcRenderer.invoke('aegis-open-server-setup'),
  retryServerConnection: () => ipcRenderer.invoke('aegis-retry-server-connection'),
  getServerInfo: () => ipcRenderer.invoke('aegis-get-server-info'),
  onRemoteDesktopDetected: (callback) => {
    const listener = (event, data) => callback(data);
    ipcRenderer.on('aegis-remote-desktop-detected', listener);
    return () => {
      ipcRenderer.removeListener('aegis-remote-desktop-detected', listener);
    };
  },
});
