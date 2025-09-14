# Employees Page - Department and Job Position Filters

Plan and step tracking for adding filters to employees_page.html and wiring logic in js/employees.js.

## Tasks

- [x] Update employees_page.html
  - [x] Add two dropdowns in the search-add bar:
    - Job Position filter (id: `filter-job-position-emp`) with default "All Job Positions"
    - Department filter (id: `filter-department-emp`) with default "All Departments"
  - [x] Add data attributes to employee cards to support filtering:
    - `data-job-position`
    - `data-department`

- [x] Update js/employees.js
  - [x] Query and wire the new filter selects
  - [x] Populate filter options dynamically from current employee cards (unique values)
  - [x] Implement `applyFilters()` to filter by:
    - Search term (matches name)
    - Selected job position
    - Selected department
  - [x] Add event listeners:
    - Search input typing and search button click trigger filtering
    - Change on both selects triggers filtering
  - [x] Ensure robust parsing:
    - Prefer data attributes
    - Fallback to parsing text content for "Name:", "Job Position:", and "Department:" labels

- [x] Align filter styling to match schedule_page.html using Tailwind classes for selects and layout

- [ ] Manual verification
  - [ ] Confirm dropdowns render correctly
  - [ ] Confirm filtering hides/shows cards as expected
  - [ ] Confirm search + filters work in combination

## Notes

- No CSS changes required for functionality; the existing layout should adapt to the added controls.
- Filtering will work with current static cards and will be resilient to future cards if they include either data attributes or the same labeled text structure.
