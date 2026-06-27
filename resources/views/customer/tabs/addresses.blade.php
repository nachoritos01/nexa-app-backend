<div x-data="{ showForm: false, editId: null, form: { label: 'casa', street: '', district: '', city: '', state: '', zip: '', references: '' } }">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-900">My Addresses</h2>
        @if($addresses->count() < 5)
            <button @click="showForm = true; editId = null; form = { label: 'casa', street: '', district: '', city: '', state: '', zip: '', references: '' }"
                    class="bg-primary-600 hover:bg-primary-700 text-white font-medium text-sm py-2 px-4 rounded-xl transition-colors">
                + Add
            </button>
        @endif
    </div>

    {{-- Address list --}}
    @if($addresses->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-gray-500 mb-4">You have no saved addresses.</p>
            <button @click="showForm = true" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Add Address
            </button>
        </div>
    @else
        <div class="space-y-4">
            @foreach($addresses as $address)
                <div class="bg-white rounded-2xl shadow-sm p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-semibold text-gray-900 capitalize">{{ $address->label }}</span>
                                @if($address->is_default)
                                    <span class="px-2 py-0.5 text-xs font-semibold bg-primary-100 text-primary-700 rounded-full">DEFAULT</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600">{{ $address->street }}</p>
                            @if($address->district)
                                <p class="text-sm text-gray-500">{{ $address->district }}</p>
                            @endif
                            <p class="text-sm text-gray-500">
                                {{ $address->city }}{{ $address->state ? ', '.$address->state : '' }}{{ $address->zip ? ' ZIP '.$address->zip : '' }}
                            </p>
                            @if($address->references)
                                <p class="text-xs text-gray-400 mt-1">Ref: {{ $address->references }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @unless($address->is_default)
                                <form method="POST" action="{{ route('customer.addresses.default', $address) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs text-primary-600 hover:text-primary-700 font-medium">Set as default</button>
                                </form>
                            @endunless
                            <button @click="editId = {{ $address->id }}; showForm = true; form = {
                                label: '{{ $address->label }}',
                                street: '{{ addslashes($address->street ?? '') }}',
                                district: '{{ addslashes($address->district ?? '') }}',
                                city: '{{ addslashes($address->city ?? '') }}',
                                state: '{{ $address->state ?? '' }}',
                                zip: '{{ $address->zip ?? '' }}',
                                references: '{{ addslashes($address->references ?? '') }}'
                            }" class="text-xs text-gray-500 hover:text-gray-700 font-medium">Edit</button>
                            <form method="POST" action="{{ route('customer.addresses.destroy', $address) }}"
                                  onsubmit="return confirm('Delete this address?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add/Edit form modal --}}
    <div x-show="showForm" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" @keydown.escape.window="showForm = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6" @click.away="showForm = false">
            <h3 class="text-lg font-bold text-gray-900 mb-4" x-text="editId ? 'Edit Address' : 'New Address'"></h3>

            <form :action="editId ? '{{ url('my-account/addresses') }}/' + editId : '{{ route('customer.addresses.store') }}'" method="POST" class="space-y-4">
                @csrf
                <template x-if="editId">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                    <select name="label" x-model="form.label" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="casa">Home</option>
                        <option value="oficina">Office</option>
                        <option value="otro">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Street and number</label>
                    <input type="text" name="street" x-model="form.street" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                    <input type="text" name="district" x-model="form.district"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                        <input type="text" name="city" x-model="form.city" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                        <input type="text" name="state" x-model="form.state"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Zip Code</label>
                    <input type="text" name="zip" x-model="form.zip" maxlength="10"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">References</label>
                    <textarea name="references" x-model="form.references" rows="2"
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-xl transition-colors">
                        <span x-text="editId ? 'Save Changes' : 'Add'"></span>
                    </button>
                    <button type="button" @click="showForm = false" class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
