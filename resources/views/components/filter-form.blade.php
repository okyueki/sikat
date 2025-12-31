<form method="GET" class="filter-form">
    <div class="form-row">
        <div class="form-group">
            <label for="start">Dari Tanggal</label>
            <input
                type="datetime-local"
                id="start"
                name="start"
                value="{{ $startDate }}"
                class="form-control"
            />
        </div>

        <div class="form-group">
            <label for="end">Sampai Tanggal</label>
            <input
                type="datetime-local"
                id="end"
                name="end"
                value="{{ $endDate }}"
                class="form-control"
            />
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </div>
</form>