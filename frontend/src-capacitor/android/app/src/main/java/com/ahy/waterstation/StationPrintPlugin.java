package com.ahy.waterstation;

import android.content.Context;
import android.print.PrintAttributes;
import android.print.PrintManager;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

@CapacitorPlugin(name = "StationPrint")
public class StationPrintPlugin extends Plugin {
    @PluginMethod
    public void printReceipt(PluginCall call) {
        getActivity().runOnUiThread(() -> {
            try {
                PrintManager manager = (PrintManager) getActivity().getSystemService(Context.PRINT_SERVICE);
                if (manager == null) {
                    call.reject("Printing is unavailable on this device.");
                    return;
                }
                manager.print("AHY Water Station Receipt",
                    getBridge().getWebView().createPrintDocumentAdapter("AHY Water Station Receipt"),
                    new PrintAttributes.Builder().build());
                call.resolve();
            } catch (Exception error) {
                call.reject("Unable to open Android printing.", error);
            }
        });
    }
}
