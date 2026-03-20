<div>
    <x-nino.button variant="secondary" wire:click="sync" wire:loading.attr="disabled" wire:target="sync">
        <span wire:loading.remove wire:target="sync" class="flex items-center">
            <span class="material-symbols-outlined mr-1.5 text-[1.1em]">refresh</span>
            Sync Stock
        </span>
        <span wire:loading wire:target="sync" class="flex items-center">
            <span class="animate-spin material-symbols-outlined mr-1.5 text-[1.1em]">refresh</span>
            Syncing...
        </span>
    </x-nino.button>
</div>
