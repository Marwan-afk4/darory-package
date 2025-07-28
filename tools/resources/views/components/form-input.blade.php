<div class="form-floating mb-3 {{ $required ? 'required' : '' }}">
    <input 
        type="{{ $type }}" 
        name="{{ $name }}" 
        id="{{ $name }}"
        placeholder="{{ $label }}"
        class="form-control @error($name) is-invalid @enderror" 
        value="{{ old($name, $value) }}" 
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        @foreach($attrs ?? [] as $attribute => $v)
            @if(is_numeric($attribute))
                {{ $v }}
            @else
                {{ $attribute }}="{{ $v }}"
            @endif
        @endforeach
    >
    <label for="{{ $name }}">{{ $label }}</label>
    
    @error($name)
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>
