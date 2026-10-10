/** The user object returned by the backend's login and `me` endpoints. */
export interface SessionUser {
  id: string
  name: string
  email: string
  role: string
  /** Null for global users and when the backend's tenancy module is removed. */
  tenant_id: string | null
}

/** Page description returned with every list endpoint (`RespondsWithJson::paginated()` on the backend). */
export interface PageMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

/** The envelope list endpoints answer with: `{ data: [...], meta: {...} }`. */
export interface Paginated<T> {
  data: T[]
  meta: PageMeta
}

/** One column of `AppDataTable`. `key` is the row property to show. */
export interface TableColumn {
  key: string
  label: string
}
