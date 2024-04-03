<!-- P2PKassa - Project ID -->
<div class="form-group row">
    <label class="col-sm-3 control-label mt-11 f-14 fw-bold text-md-end" for="p2pkassa_project_id">{{ __('Project ID') }}</label>
    <div class="col-sm-6">
        <input class="form-control f-14" name="p2pkassa[project_id]" type="text" placeholder="{{ __('P2PKassa Project ID') }}"
        value="{{ isset($currencyPaymentMethod->method_data) ? json_decode($currencyPaymentMethod->method_data)->project_id : '' }}" id="p2pkassa_project_id" required>
        @if ($errors->has('p2pkassa[project_id]'))
            <span class="help-block">
                <strong>{{ $errors->first('p2pkassa[project_id]') }}</strong>
            </span>
        @endif
    </div>
</div>
<div class="clearfix"></div>
<!-- P2PKassa - Api Key -->
<div class="form-group row">
    <label class="col-sm-3 control-label mt-11 f-14 fw-bold text-md-end" for="p2pkassa_apikey">{{ __('Api Key') }}</label>
    <div class="col-sm-6">
        <input class="form-control f-14" name="p2pkassa[apikey]" type="text" id="p2pkassa_apikey" value="{{ isset($currencyPaymentMethod->method_data) ? json_decode($currencyPaymentMethod->method_data)->apikey : '' }}" placeholder="{{ __('P2PKassa Api Key') }}" required>
        @if ($errors->has('p2pkassa[apikey]'))
            <span class="help-block">
                <strong>{{ $errors->first('p2pkassa[apikey]') }}</strong>
            </span>
        @endif
    </div>
</div>
<div class="clearfix"></div>