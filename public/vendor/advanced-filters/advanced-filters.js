/**
 * Alpine controller for the Advanced Filters panel.
 *
 * Usage (zero-build / CDN): just include this file after Alpine — it self-registers
 * the `advancedFiltersPanel` component on `alpine:init`.
 *
 * Usage (Vite/bundler):
 *     import Alpine from 'alpinejs'
 *     import { advancedFiltersPanel } from './vendor/advanced-filters'
 *     Alpine.data('advancedFiltersPanel', advancedFiltersPanel)
 *     Alpine.start()
 */

/**
 * Fallback operator labels, used only when a field definition predates `clauseItems`.
 * The server is the source of truth — see Clause::label().
 */
export const OPERATOR_LABELS = {
	contains: 'Contains',
	not_contains: 'Does not contain',
	starts_with: 'Starts with',
	ends_with: 'Ends with',
	equals: 'Equals',
	not_equals: 'Not equals',
	greater_than: 'Greater than',
	less_than: 'Less than',
	greater_than_or_equal: '≥',
	less_than_or_equal: '≤',
	between: 'Between',
	in: 'Is any of',
	not_in: 'Is none of',
	is_empty: 'Is empty',
	is_not_empty: 'Is not empty',
}

/** Value shapes — mirrors ClauseContract::SHAPE_*. */
export const SHAPE_NONE = 'none'
export const SHAPE_SINGLE = 'single'
export const SHAPE_RANGE = 'range'
export const SHAPE_MULTI = 'multi'

/** Same fallback role as OPERATOR_LABELS: only consulted without `clauseItems`. */
const FALLBACK_SHAPES = {
	is_empty: SHAPE_NONE,
	is_not_empty: SHAPE_NONE,
	between: SHAPE_RANGE,
	in: SHAPE_MULTI,
	not_in: SHAPE_MULTI,
}

const POSITIVE_STRING_OPERATORS = ['contains', 'starts_with', 'ends_with', 'equals']

/** How many values an operator takes, per the server's clause definition. */
export function clauseShape(field, operator) {
	if (!operator) return SHAPE_SINGLE
	const item = field && Array.isArray(field.clauseItems) ? field.clauseItems.find(c => c.value === operator) : null

	return (item && item.shape) || FALLBACK_SHAPES[operator] || SHAPE_SINGLE
}

/* ------------------------------------------------------------------------- *
 * Filter type handlers
 *
 * One entry per filter type. Every key is optional — anything a handler leaves
 * out falls back to the shape-driven default below, so a custom type usually
 * needs one or two functions at most:
 *
 *     import { registerFilterType } from './vendor/advanced-filters'
 *
 *     registerFilterType('boolean', {
 *         emptyValue: () => '1',
 *         canAdd: pending => pending.value === '1' || pending.value === '0',
 *         chipValue: filter => (filter.value === '1' ? 'Yes' : 'No'),
 *     })
 * ------------------------------------------------------------------------- */

const TYPE_HANDLERS = {}

export function registerFilterType(type, handler = {}) {
	TYPE_HANDLERS[type] = { ...(TYPE_HANDLERS[type] || {}), ...handler }

	return TYPE_HANDLERS[type]
}

export function filterTypeHandler(type) {
	return TYPE_HANDLERS[type] || null
}

/** Resolve one handler function for a field, or undefined. */
function hook(field, name) {
	const handler = field ? TYPE_HANDLERS[field.type] : null

	return handler && typeof handler[name] === 'function' ? handler[name] : undefined
}

function defaultEmptyValue(operator, field) {
	return clauseShape(field, operator) === SHAPE_MULTI ? [] : ''
}

function defaultCanAdd(pending, field) {
	const shape = clauseShape(field, pending.operator)

	if (shape === SHAPE_NONE) return true
	if (shape === SHAPE_MULTI) return Array.isArray(pending.value) && pending.value.length > 0
	if (shape === SHAPE_RANGE) {
		return String(pending.value ?? '').trim() !== '' && String(pending.valueTo ?? '').trim() !== ''
	}

	return String(pending.value ?? '').trim() !== ''
}

// Numbers need their own predicate and coercion; everything else is shape-driven.
registerFilterType('number', {
	canAdd(pending, field) {
		const shape = clauseShape(field, pending.operator)

		if (shape === SHAPE_NONE) return true
		if (shape === SHAPE_MULTI) return Array.isArray(pending.value) && pending.value.length > 0
		if (shape === SHAPE_RANGE) {
			return isFilledNumber(pending.value) && isFilledNumber(pending.valueTo)
		}

		return isFilledNumber(pending.value)
	},
	coerce(pending, field) {
		const shape = clauseShape(field, pending.operator)
		const row = { field: pending.field, operator: pending.operator, value: pending.value, valueTo: pending.valueTo }

		if (shape === SHAPE_MULTI || shape === SHAPE_NONE) return row

		row.value = Number(pending.value)
		if (shape === SHAPE_RANGE) row.valueTo = Number(pending.valueTo)

		return row
	},
})

registerFilterType('string', {
	// Multi-line input means OR across the lines, but only for positive clauses —
	// "does not contain A or B" would read as the opposite of what it does.
	supportsMultilineOr(operator) {
		return POSITIVE_STRING_OPERATORS.includes(operator)
	},
})

registerFilterType('date', {})

registerFilterType('select', {
	chipValue(filter, field, { resolveLabel }) {
		if (Array.isArray(filter.value)) {
			return filter.value.map(v => resolveLabel(field, v)).join(', ')
		}

		return resolveLabel(field, filter.value)
	},
})

function isFilledNumber(value) {
	return String(value ?? '').trim() !== '' && !Number.isNaN(Number(value))
}

function emptyPending() {
	return { field: null, operator: null, value: '', valueTo: '' }
}

/** Shared validation predicate — does the pending row have a usable value? */
export function canAddRow(pending, field) {
	if (!pending.field || !pending.operator) return false

	const custom = hook(field, 'canAdd')

	return custom ? !!custom(pending, field) : defaultCanAdd(pending, field)
}

/** Build the row sent to the backend from the pending state. */
export function coerceRow(pending, field) {
	const custom = hook(field, 'coerce')
	if (custom) return custom(pending, field)

	return { field: pending.field, operator: pending.operator, value: pending.value, valueTo: pending.valueTo }
}

/** The initial `pending.value` for an operator. */
export function emptyValueFor(operator, field) {
	const custom = hook(field, 'emptyValue')

	return custom ? custom(operator, field) : defaultEmptyValue(operator, field)
}

/**
 * Serialise active rows into a URL carrying the wire contract.
 *
 * Framework-free on purpose: Alpine, a Vue/React component and an Inertia page all need
 * the same query string, and this is the only place that shape is written.
 *
 * Existing params (sort, per-page, …) are preserved; stale filter rows and `page` are
 * dropped so a new filter always lands on page 1.
 *
 * @param {Array} active  normalised filter rows
 * @param {{baseUrl?: string, queryKey?: string, search?: string}} config
 */
export function buildFilterUrl(active = [], config = {}) {
	const hasLocation = typeof window !== 'undefined' && window.location
	const origin = hasLocation ? window.location.origin : 'http://localhost'
	const path = config.baseUrl ? new URL(config.baseUrl, origin).pathname : hasLocation ? window.location.pathname : '/'

	const qk = config.queryKey || 'column_filters'
	const search = config.search ?? (hasLocation ? window.location.search : '')
	const params = new URLSearchParams(search)

	for (const key of [...params.keys()]) {
		if (key === qk || key.startsWith(`${qk}[`) || key === 'page') {
			params.delete(key)
		}
	}

	active.forEach((f, i) => {
		params.set(`${qk}[${i}][field]`, f.field)
		params.set(`${qk}[${i}][operator]`, f.operator)
		if (Array.isArray(f.value)) {
			f.value.forEach((v, j) => params.set(`${qk}[${i}][value][${j}]`, v))
		} else if (f.value !== undefined && f.value !== null && f.value !== '') {
			params.set(`${qk}[${i}][value]`, f.value)
		}
		if (f.valueTo !== undefined && f.valueTo !== null && f.valueTo !== '') {
			params.set(`${qk}[${i}][valueTo]`, f.valueTo)
		}
	})

	const qs = params.toString()

	return path + (qs ? `?${qs}` : '')
}

/** Map a raw value to its display label (select options resolve to their labels). */
export function resolveOptionLabel(field, raw) {
	if (field && Array.isArray(field.optionItems)) {
		const hit = field.optionItems.find(o => String(o.value) === String(raw))
		if (hit) return hit.label
	}

	return raw
}

/**
 * The two-step builder behaviour (field → operator → value), shared between the
 * Blade/Alpine panel and the Livewire adapter. `onAdd(row)` is called when a valid
 * filter row is applied; the host decides what to do with it.
 */
export function builderMixin(init = {}) {
	return {
		fields: init.fields || [],
		prefix: init.prefix || 'af',
		onAdd: init.onAdd || (() => {}),

		open: false,
		step: 'field',
		pending: emptyPending(),

		selectedField() {
			return this.fields.find(f => f.key === this.pending.field) || null
		},
		fieldByKey(key) {
			return this.fields.find(f => f.key === key) || null
		},
		availableOperators() {
			const f = this.selectedField()
			return f && Array.isArray(f.clauses) ? f.clauses : []
		},
		optionItems() {
			const f = this.selectedField()
			return f && Array.isArray(f.optionItems) ? f.optionItems : []
		},
		operatorLabel(op, fieldKey = null) {
			// A chip's operator belongs to its own field, not the one being edited.
			const f = fieldKey === null ? this.selectedField() : this.fieldByKey(fieldKey)
			const item = f && Array.isArray(f.clauseItems) ? f.clauseItems.find(c => c.value === op) : null

			return (item && item.label) || OPERATOR_LABELS[op] || op
		},
		fieldLabel(key) {
			const f = this.fieldByKey(key)
			return f ? f.label : key
		},
		shape() {
			return clauseShape(this.selectedField(), this.pending.operator)
		},
		needsValueInput() {
			const f = this.selectedField()
			const custom = hook(f, 'needsValueInput')
			if (custom) return !!custom(this.pending.operator, f)

			return !!this.pending.operator && this.shape() !== SHAPE_NONE
		},
		isBetween() {
			return this.shape() === SHAPE_RANGE
		},
		isMultiValue() {
			return this.shape() === SHAPE_MULTI
		},
		supportsMultilineOr() {
			const f = this.selectedField()
			const custom = hook(f, 'supportsMultilineOr')

			return custom ? !!custom(this.pending.operator, f) : false
		},
		canAdd() {
			return canAddRow(this.pending, this.selectedField())
		},

		toggleMenu() {
			this.open = !this.open
			if (!this.open) this.resetMenu()
		},
		closeMenu() {
			this.open = false
			this.resetMenu()
		},
		resetMenu() {
			this.step = 'field'
			this.pending = emptyPending()
		},
		selectField(field) {
			this.pending.field = field.key
			this.onFieldChange()
			this.step = 'configure'
		},
		onFieldChange() {
			const ops = this.availableOperators()
			this.pending.operator = ops[0] || null
			this.resetPendingValue()
		},
		onOperatorChange() {
			this.resetPendingValue()
		},
		resetPendingValue() {
			this.pending.value = emptyValueFor(this.pending.operator, this.selectedField())
			this.pending.valueTo = ''
		},
		goBackToFields() {
			this.step = 'field'
			this.pending = emptyPending()
		},
		addPending() {
			if (!this.canAdd()) return
			const row = coerceRow(this.pending, this.selectedField())
			this.onAdd(row)
			this.closeMenu()
		},
	}
}

/**
 * Standalone Livewire builder — `onAdd` forwards the row to the Livewire component,
 * which owns the active list and re-queries server-side.
 */
export function advancedFiltersBuilder(init = {}) {
	return builderMixin(init)
}

export function advancedFiltersPanel(init = {}) {
	return {
		...builderMixin(init),

		active: init.active || [],
		config: init.config || {},

		// Owns the active list: applied rows are pushed here, then the table reloads.
		addPending() {
			if (!this.canAdd()) return
			this.active.push(coerceRow(this.pending, this.selectedField()))
			this.closeMenu()
			this.apply()
		},

		remove(i) {
			this.active.splice(i, 1)
			this.apply()
		},

		clearAll() {
			this.active = []
			this.apply()
		},

		// ----- chip display -----
		resolveLabel(fieldKey, raw) {
			return resolveOptionLabel(this.fieldByKey(fieldKey), raw)
		},

		chipValue(filter) {
			const field = this.fieldByKey(filter.field)
			const shape = clauseShape(field, filter.operator)

			if (shape === SHAPE_NONE) return ''
			if (shape === SHAPE_RANGE) return `${filter.value} – ${filter.valueTo}`

			const custom = hook(field, 'chipValue')
			if (custom) {
				return custom(filter, field, { resolveLabel: resolveOptionLabel, shape })
			}

			if (Array.isArray(filter.value)) {
				return filter.value.map(v => resolveOptionLabel(field, v)).join(', ')
			}

			if (typeof filter.value === 'string' && /\r?\n/.test(filter.value)) {
				const lines = filter.value
					.split(/\r?\n/)
					.map(s => s.trim())
					.filter(Boolean)
				if (lines.length > 1) {
					const shown = lines.slice(0, 5).map(l => resolveOptionLabel(field, l))
					const extra = lines.length - shown.length
					return `(${shown.join(' or ')}${extra > 0 ? ` or +${extra} more` : ''})`
				}
				if (lines.length === 1) return resolveOptionLabel(field, lines[0])
			}

			return resolveOptionLabel(field, filter.value)
		},

		chipKey(filter, i) {
			return `${filter.field}-${filter.operator}-${i}`
		},

		// ----- apply (reload) -----
		buildUrl() {
			return buildFilterUrl(this.active, this.config)
		},

		apply() {
			const url = this.buildUrl()

			// An SPA router (Inertia, Turbo, your own) takes over here instead of a
			// full-page navigate. Kept as a callback so the package stays dependency-free.
			if (typeof this.config.onApply === 'function') {
				this.config.onApply(url, this.active)

				return
			}

			if (this.config.mode === 'fetch' && this.config.target) {
				this.applyViaFetch(url)

				return
			}

			window.location.assign(url)
		},

		applyViaFetch(url) {
			const target = document.querySelector(this.config.target)
			if (!target) {
				window.location.assign(url)
				return
			}

			fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' } })
				.then(r => r.text())
				.then(html => {
					const doc = new DOMParser().parseFromString(html, 'text/html')
					const fresh = doc.querySelector(this.config.target)
					if (fresh) {
						target.innerHTML = fresh.innerHTML
					}
					window.history.pushState({}, '', url)
				})
				.catch(() => window.location.assign(url))
		},
	}
}

// Zero-build self-registration.
if (typeof document !== 'undefined') {
	document.addEventListener('alpine:init', () => {
		if (window.Alpine) {
			window.Alpine.data('advancedFiltersPanel', advancedFiltersPanel)
			window.Alpine.data('advancedFiltersBuilder', advancedFiltersBuilder)
		}
	})
}

// Registering a filter type needs to work without a bundler too.
if (typeof window !== 'undefined') {
	window.AdvancedFilters = {
		...(window.AdvancedFilters || {}),
		registerFilterType,
		filterTypeHandler,
		clauseShape,
		resolveOptionLabel,
		buildFilterUrl,
		canAddRow,
		coerceRow,
		emptyValueFor,
		advancedFiltersPanel,
		advancedFiltersBuilder,
		OPERATOR_LABELS,
	}
}
