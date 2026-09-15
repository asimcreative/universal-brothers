{{--
    The fields of one page-builder section (or one saved section), or of one
    item inside a repeating list.

    $fields     field definitions (BlockRegistry)
    $values     current values
    $name       form name prefix, e.g. sections[s_ab12][data]
    $errorKey   validation key prefix, e.g. sections.s_ab12.data
    $idPrefix   element id prefix, unique on the page
--}}
@php use App\Support\PageBuilder\BlockRegistry; @endphp

<div class="row g-3 pb-fields">
    @foreach($fields as $field)
        @php
            $fName = $name.'['.$field['name'].']';
            $fKey = $errorKey.'.'.$field['name'];
            $fId = $idPrefix.'-'.$field['name'];
            $fValue = $values[$field['name']] ?? BlockRegistry::emptyValue($field);
            $fHelpId = ! empty($field['help']) ? $fId.'-help' : null;
            $fInvalid = $errors->has($fKey);
            $fCol = in_array($field['type'], ['richtext', 'items', 'image'], true) ? 12 : ($field['col'] ?? 12);
            $fShowWhen = $field['show_when'] ?? null;
            $fVisible = true;
            foreach ($fShowWhen ?? [] as $other => $wanted) {
                $fVisible = $fVisible && (string) ($values[$other] ?? '') === (string) $wanted;
            }
        @endphp

        @if($field['type'] === 'hidden')
            <input type="hidden" name="{{ $fName }}" value="{{ $fValue }}">
            @continue
        @endif

        <div class="col-md-{{ $fCol }}" @if($fShowWhen) data-show-when='@json($fShowWhen)' @endif @unless($fVisible) hidden @endunless>
            @switch($field['type'])
                @case('richtext')
                    <x-admin.rich-text :name="$fName" :id="$fId" :label="$field['label']" :value="$fValue" :profile="$field['profile'] ?? 'standard'"
                        :max="$field['max'] ?? null" :help="$field['help'] ?? null" :required="! empty($field['required'])" :error-key="$fKey" />
                    @break

                @case('image')
                    <x-admin.image-picker :name="$fName" :label="$field['label']" :value="$fValue" :alt="$field['alt'] ?? true"
                        :required="! empty($field['required'])" :help="$field['help'] ?? null" :error-key="$fKey" />
                    @break

                @case('toggle')
                    <input type="hidden" name="{{ $fName }}" value="0">
                    <div class="form-check form-switch pb-toggle">
                        <input class="form-check-input" type="checkbox" role="switch" id="{{ $fId }}" name="{{ $fName }}" value="1" @checked((bool) $fValue) @if($fHelpId) aria-describedby="{{ $fHelpId }}" @endif>
                        <label class="form-check-label" for="{{ $fId }}">{{ $field['label'] }}</label>
                    </div>
                    @if($fHelpId)<div class="form-help" id="{{ $fHelpId }}">{{ $field['help'] }}</div>@endif
                    @break

                @case('items')
                    @php
                        $iMax = $field['max_items'] ?? 12;
                        $iItems = is_array($fValue) ? array_values($fValue) : [];
                        $iDefaults = collect($field['fields'])->mapWithKeys(fn ($f) => [$f['name'] => $f['default'] ?? BlockRegistry::emptyValue($f)])->all();
                    @endphp
                    <fieldset class="pb-items" data-items data-max="{{ $iMax }}" data-item-label="{{ $field['item_label'] ?? 'Item' }}" aria-describedby="{{ $fId }}-count">
                        <legend class="form-label">{{ $field['label'] }} @if(! empty($field['min_items']))<span class="required-mark" aria-hidden="true">*</span>@endif</legend>
                        <div class="pb-items-list" data-items-list>
                            @foreach($iItems as $i => $item)
                                @include('admin.pages.partials.item', ['field' => $field, 'item' => $item, 'index' => $i, 'name' => $fName, 'errorKey' => $fKey, 'idPrefix' => $fId])
                            @endforeach
                        </div>
                        <template data-item-template>
                            @include('admin.pages.partials.item', ['field' => $field, 'item' => $iDefaults, 'index' => '__ITEM__', 'name' => $fName, 'errorKey' => $fKey, 'idPrefix' => $fId])
                        </template>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-item-add @if(count($iItems) >= $iMax) disabled @endif>
                                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add {{ strtolower($field['item_label'] ?? 'item') }}
                            </button>
                            <span class="small text-muted" id="{{ $fId }}-count" data-items-count>{{ count($iItems) }} of up to {{ $iMax }}</span>
                        </div>
                        <x-admin.error :name="$fKey" />
                    </fieldset>
                    @break

                @default
                    <label for="{{ $fId }}" class="form-label">{{ $field['label'] }}@if(! empty($field['required'])) <span class="required-mark" aria-hidden="true">*</span>@endif</label>
                    @php
                        $fDescribed = trim(($fHelpId ?? '').' '.($fInvalid ? 'error-'.\Illuminate\Support\Str::slug(str_replace('.', '-', $fKey)) : ''));
                    @endphp

                    @if($field['type'] === 'textarea')
                        <textarea id="{{ $fId }}" name="{{ $fName }}" rows="{{ $field['rows'] ?? 3 }}" @if(! empty($field['max'])) maxlength="{{ $field['max'] }}" @endif
                                  @class(['form-control', 'is-invalid' => $fInvalid]) @if($fDescribed) aria-describedby="{{ $fDescribed }}" @endif
                                  @if(! empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
                                  data-summary-source="{{ $field['name'] }}">{{ $fValue }}</textarea>
                    @elseif(in_array($field['type'], ['select', 'icon'], true))
                        <div @class(['pb-icon-select' => $field['type'] === 'icon'])>
                            @if($field['type'] === 'icon')<span class="pb-icon-preview" aria-hidden="true"><i class="bi {{ $fValue ?: 'bi-star' }}" data-icon-preview></i></span>@endif
                            <select id="{{ $fId }}" name="{{ $fName }}" @class(['form-select', 'is-invalid' => $fInvalid]) @if($fDescribed) aria-describedby="{{ $fDescribed }}" @endif
                                    data-field-name="{{ $field['name'] }}" @if($field['type'] === 'icon') data-icon-select @endif>
                                @foreach(BlockRegistry::options($field) as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected((string) $fValue === (string) $optionValue)>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="{{ $field['type'] === 'number' ? 'number' : 'text' }}" id="{{ $fId }}" name="{{ $fName }}" value="{{ $fValue }}"
                               @class(['form-control', 'is-invalid' => $fInvalid])
                               @if(! empty($field['max']) && $field['type'] === 'text') maxlength="{{ $field['max'] }}" @endif
                               @if($field['type'] === 'link') inputmode="url" autocomplete="off" data-link-field placeholder="{{ $field['placeholder'] ?? '/contact or https://…' }}"
                               @elseif($field['type'] === 'video') inputmode="url" autocomplete="off" data-video-field placeholder="{{ $field['placeholder'] ?? 'https://www.youtube.com/watch?v=…' }}"
                               @elseif(! empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
                               @if($fDescribed) aria-describedby="{{ $fDescribed }}" @endif
                               data-summary-source="{{ $field['name'] }}">
                        @if($field['type'] === 'link' && empty($field['help']))
                            <div class="form-help" id="{{ $fId }}-linkhelp">A page on this website starts with a slash, like /contact. Another website starts with https://.</div>
                        @endif
                    @endif
                    @if($fHelpId)<div class="form-help" id="{{ $fHelpId }}">{{ $field['help'] }}</div>@endif
                    <p class="small text-danger mb-0 mt-1" data-field-feedback role="status" hidden></p>
                    <x-admin.error :name="$fKey" />
            @endswitch
        </div>
    @endforeach
</div>
