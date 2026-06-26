<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Currency;
use App\Services\Currency\CurrencyService;

class CurrencyController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {
        // Staff Permission Check
        $this->middleware(['permission:currency_setup'])->only('currency','create','edit', 'updateApiSettings', 'syncRates', 'testConnection');
    }

    public function changeCurrency(Request $request)
    {
        $currency = Currency::where('code', $request->currency_code)->first();
        $request->session()->put('currency_code', $request->currency_code);
        $request->session()->put('currency_symbol', $currency->symbol);
        $request->session()->put('currency_exchange_rate', $currency->exchange_rate);
    	flash(translate('Currency changed to ').$currency->name)->success();
    }

    public function currency(Request $request)
    {
        $sort_search =null;
        $currencies = Currency::orderBy('created_at', 'desc');
        if ($request->has('search')){
            $sort_search = $request->search;
            $currencies = $currencies->where('name', 'like', '%'.$sort_search.'%');
        }
        $currencies = $currencies->paginate(10);

        $active_currencies = Currency::where('status', 1)->get();
        $currencySettings = $this->currencyService->settings();
        return view('backend.setup_configurations.currencies.index', compact('currencies', 'active_currencies','sort_search', 'currencySettings'));
    }

    public function updateCurrency(Request $request)
    {
        return $this->updateYourCurrency($request);
    }

    public function updateYourCurrency(Request $request)
    {
        $currency = Currency::findOrFail($request->id);
        $currency->name = $request->name;
        $currency->symbol = $request->symbol;
        $currency->code = $request->code;
        $currency->exchange_rate = $request->exchange_rate;
        $currency->decimal_places = $request->decimal_places ?? $currency->decimal_places ?? 2;
        $currency->symbol_position = $request->symbol_position ?? $currency->symbol_position ?? 'prefix';
        $currency->status = $currency->status;
        if($currency->save()){
            flash(translate('Currency updated successfully'))->success();
            return redirect()->route('currency.index');
        }
        else {
            flash(translate('Something went wrong'))->error();
            return redirect()->route('currency.index');
        }
    }

    public function create()
    {
        return view('backend.setup_configurations.currencies.create');
    }

    public function edit(Request $request)
    {
        $currency = Currency::findOrFail($request->id);
        return view('backend.setup_configurations.currencies.edit', compact('currency'));
    }

    public function store(Request $request)
    {
        $currency = new Currency;
        $currency->name = $request->name;
        $currency->symbol = $request->symbol;
        $currency->code = $request->code;
        $currency->exchange_rate = $request->exchange_rate;
        $currency->decimal_places = $request->decimal_places ?? 2;
        $currency->symbol_position = $request->symbol_position ?? 'prefix';
        $currency->status = '0';
        if($currency->save()){
            flash(translate('Currency updated successfully'))->success();
            return redirect()->route('currency.index');
        }
        else {
            flash(translate('Something went wrong'))->error();
            return redirect()->route('currency.index');
        }
    }

    public function update_status(Request $request)
    {
        $currency = Currency::findOrFail($request->id);
        if($request->status == 0){
            if (get_setting('system_default_currency') == $currency->id) {
                return 0;
            }
        }
        $currency->status = $request->status;
        $currency->save();
        return 1;
    }

    public function updateApiSettings(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|max:50',
            'driver' => 'nullable|string|max:50',
            'base_currency_code' => 'required|exists:currencies,code',
            'default_display_currency_code' => 'required|exists:currencies,code',
            'sync_frequency' => 'required|in:hourly,daily,weekly',
            'api_key' => 'nullable|string|max:255',
            'auto_sync_enabled' => 'nullable|boolean',
        ]);

        $this->currencyService->upsertSettings($validated);

        flash(translate('Currency API settings updated successfully.'))->success();
        return redirect()->route('currency.index');
    }

    public function syncRates()
    {
        $result = $this->currencyService->sync(true);
        if (($result['status'] ?? null) === 'failed') {
            flash($result['message'] ?? 'Currency sync failed.')->error();
        } else {
            flash($result['message'] ?? 'Currency sync finished.')->success();
        }

        return redirect()->route('currency.index');
    }

    public function testConnection()
    {
        try {
            $this->currencyService->testConnection();
            flash(translate('Currency provider connection test passed.'))->success();
        } catch (\Throwable $exception) {
            flash($exception->getMessage())->error();
        }

        return redirect()->route('currency.index');
    }
}
