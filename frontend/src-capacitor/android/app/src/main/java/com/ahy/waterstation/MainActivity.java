package com.ahy.waterstation;

import com.getcapacitor.BridgeActivity;
import android.os.Bundle;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        registerPlugin(StationPrintPlugin.class);
        super.onCreate(savedInstanceState);
    }
}
