const ISO_DATE = /^\d{4}-\d{2}-\d{2}$/

// Nilai dari URL tidak bisa dipercaya: yang tidak valid dianggap tidak ada.
export function parsePage(value: string | null): number {
  const page = Number(value)
  return Number.isInteger(page) && page >= 1 ? page : 1
}

export function parseIsoDate(value: string | null): string {
  return value !== null && ISO_DATE.test(value) ? value : ''
}