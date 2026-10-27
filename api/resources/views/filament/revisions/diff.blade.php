{{-- Nuvarande rad mot en revisions payload, fält för fält. Ändrade rader markeras. --}}
@php
    /** @var list<array{field: string, current: ?string, proposed: ?string, changed: bool}> $rows */
    $proposedLabel ??= 'Föreslaget';
@endphp

<div class="fi-ta-ctn">
    <table class="fi-ta-table" style="width: 100%">
        <thead>
            <tr>
                <th class="fi-ta-header-cell">Fält</th>
                <th class="fi-ta-header-cell">Nuvarande</th>
                <th class="fi-ta-header-cell">{{ $proposedLabel }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="fi-ta-row" @if ($row['changed']) style="background: color-mix(in oklch, var(--warning-500) 12%, transparent)" @endif>
                    <td class="fi-ta-cell" style="vertical-align: top; white-space: nowrap">
                        <code>{{ $row['field'] }}</code>
                        @if ($row['changed'])
                            <x-filament::badge color="warning" size="sm">Ändrat</x-filament::badge>
                        @endif
                    </td>
                    <td class="fi-ta-cell" style="vertical-align: top; overflow-wrap: anywhere">{{ $row['current'] ?? '–' }}</td>
                    <td class="fi-ta-cell" style="vertical-align: top; overflow-wrap: anywhere">{{ $row['proposed'] ?? '–' }}</td>
                </tr>
            @empty
                <tr class="fi-ta-row">
                    <td class="fi-ta-cell" colspan="3">Revisionen innehåller inga fält.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
