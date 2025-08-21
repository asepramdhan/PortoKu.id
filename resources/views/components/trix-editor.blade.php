@props(['value' => ''])

<div wire:ignore x-data="{
        value: @entangle($attributes->wire('model')),
        isFocused: false,
        init() {
            let trix = this.$refs.trix;
            trix.editor.loadHTML(this.value);

            trix.addEventListener('trix-change', (event) => {
                // Don't sync immediately, wait for the submit event
                // this.value = trix.value;
            });

            trix.addEventListener('trix-focus', () => { this.isFocused = true; });
            trix.addEventListener('trix-blur', () => { this.isFocused = false; });

            // Handle file uploads
            trix.addEventListener('trix-attachment-add', (event) => {
                if (event.attachment.file) {
                    this.uploadFile(event.attachment);
                }
            });

              // FIX: Tambahkan listener untuk event 'trix-attachment-remove'
            trix.addEventListener('trix-attachment-remove', (event) => {
                const attachment = event.attachment;
                const url = attachment.attachment.attributes.values.url;
                @this.call('removeAttachment', url);
            });
            
            window.addEventListener('dragover', (e) => { e.preventDefault(); }, false);
            window.addEventListener('drop', (e) => { e.preventDefault(); }, false);
        },

        uploadFile(attachment) {
            @this.upload('photo', attachment.file,
                (uploadedFilename) => {
                    @this.call('getPhotoUrl').then(url => {
                        attachment.setAttributes({ url: url, href: url });
                    });
                },
                () => { /* Error */ },
                (event) => {
                    attachment.setUploadProgress(event.detail.progress);
                }
            );
            this.$watch('value', (newValue) => {
                if (!this.isFocused) {
                    trix.editor.loadHTML(newValue);
                }
            });
        }
    }" @trix-submit.window="@this.set('{{ $attributes->wire('model')->value() }}', $refs.trix.value); $wire.save()" class="rounded-md shadow-sm">
  <input id="{{ $attributes->get('id') ?? 'trix' }}" type="hidden">
  <trix-editor x-ref="trix" input="{{ $attributes->get('id') ?? 'trix' }}" {{ $attributes->whereDoesntStartWith('wire:model') }}></trix-editor>
</div>
