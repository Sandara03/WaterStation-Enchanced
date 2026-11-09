import { Capacitor, registerPlugin } from '@capacitor/core';
import { Directory, Encoding, Filesystem } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';

const StationPrint = registerPlugin<{ printReceipt(): Promise<void> }>('StationPrint');

export async function saveTextFile(filename: string, text: string, mime: string) {
  if (Capacitor.isNativePlatform()) {
    const safeName = filename.replace(/[^a-zA-Z0-9_.-]/g, '_');
    const { uri } = await Filesystem.writeFile({ path: safeName, data: text, directory: Directory.Cache, encoding: Encoding.UTF8 });
    await Share.share({ title: filename, dialogTitle: 'Save or share Water Station file', files: [uri] });
    return;
  }
  const url = URL.createObjectURL(new Blob([text], { type: mime }));
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export async function printReceipt() {
  if (Capacitor.isNativePlatform()) await StationPrint.printReceipt();
  else window.print();
}
