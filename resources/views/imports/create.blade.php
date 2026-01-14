@extends('layouts.app')

@section('title', 'Import Bank Statement')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Import Bank Statement</h1>
        <p class="text-gray-600 mb-6">{{ $bankAccount->account_name }} - {{ $bankAccount->bank_name }}</p>

        @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <ul class="list-disc list-inside text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('imports.store', [$organisation->id, $bankAccount->id]) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- CSV File Upload -->
            <div>
                <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-2">CSV File</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-blue-500 transition"
                     onclick="document.getElementById('csv_file').click()">
                    <input 
                        type="file" 
                        id="csv_file" 
                        name="csv_file" 
                        accept=".csv,.txt"
                        required
                        class="hidden"
                        onchange="updateFileName(this)"
                    >
                    <p class="text-gray-600">Click to select a CSV file or drag and drop</p>
                    <p class="text-gray-500 text-sm mt-1">Maximum file size: 10MB</p>
                    <p id="file-name" class="text-blue-600 font-semibold mt-2"></p>
                </div>
                @error('csv_file')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Column Mapping Section -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Column Mapping</h2>
                <p class="text-gray-600 text-sm mb-4">Specify which columns in your CSV contain each type of data (0-indexed)</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Date Column -->
                    <div>
                        <label for="date_column" class="block text-sm font-medium text-gray-700 mb-2">Date Column *</label>
                        <input 
                            type="number" 
                            id="date_column" 
                            name="date_column" 
                            value="{{ old('date_column', 0) }}"
                            min="0"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="0"
                        >
                        <p class="text-gray-600 text-xs mt-1">Column containing transaction date</p>
                        @error('date_column')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description Column -->
                    <div>
                        <label for="description_column" class="block text-sm font-medium text-gray-700 mb-2">Description Column *</label>
                        <input 
                            type="number" 
                            id="description_column" 
                            name="description_column" 
                            value="{{ old('description_column', 1) }}"
                            min="0"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="1"
                        >
                        <p class="text-gray-600 text-xs mt-1">Column containing transaction description</p>
                        @error('description_column')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Amount Column -->
                    <div>
                        <label for="amount_column" class="block text-sm font-medium text-gray-700 mb-2">Amount Column *</label>
                        <input 
                            type="number" 
                            id="amount_column" 
                            name="amount_column" 
                            value="{{ old('amount_column', 2) }}"
                            min="0"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="2"
                        >
                        <p class="text-gray-600 text-xs mt-1">Column containing transaction amount</p>
                        @error('amount_column')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Type Column -->
                    <div>
                        <label for="type_column" class="block text-sm font-medium text-gray-700 mb-2">Type Column *</label>
                        <input 
                            type="number" 
                            id="type_column" 
                            name="type_column" 
                            value="{{ old('type_column', 3) }}"
                            min="0"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="3"
                        >
                        <p class="text-gray-600 text-xs mt-1">Column containing transaction type (credit/debit)</p>
                        @error('type_column')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reference Column -->
                    <div>
                        <label for="reference_column" class="block text-sm font-medium text-gray-700 mb-2">Reference Column (Optional)</label>
                        <input 
                            type="number" 
                            id="reference_column" 
                            name="reference_column" 
                            value="{{ old('reference_column') }}"
                            min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Leave blank if not applicable"
                        >
                        <p class="text-gray-600 text-xs mt-1">Column containing transaction reference/ID</p>
                        @error('reference_column')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Supported Formats -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Supported Formats</h3>
                <p class="text-blue-800 text-sm mb-2"><strong>Date formats:</strong> YYYY-MM-DD, MM/DD/YYYY, DD/MM/YYYY, DD-MM-YYYY, YYYY/MM/DD, M d, Y, d M Y</p>
                <p class="text-blue-800 text-sm mb-2"><strong>Amount formats:</strong> 1000.00, 1,000.00, $1,000.00, 1.000,00 (European)</p>
                <p class="text-blue-800 text-sm"><strong>Type values:</strong> credit, in, +, debit, out, -</p>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-4">
                <button 
                    type="submit" 
                    class="flex-1 bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition"
                >
                    Import Transactions
                </button>
                <a 
                    href="{{ route('bank-accounts.show', [$organisation->id, $bankAccount->id]) }}" 
                    class="flex-1 bg-gray-200 text-gray-900 font-semibold py-2 rounded-lg hover:bg-gray-300 transition text-center"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function updateFileName(input) {
    const fileName = input.files[0]?.name || '';
    document.getElementById('file-name').textContent = fileName ? `Selected: ${fileName}` : '';
}
</script>
@endsection
