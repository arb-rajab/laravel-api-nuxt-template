/**
 * Turns a caught API error into both a banner message and per-field messages,
 * so every form follows the same rule instead of slightly different ones.
 *
 * Precedence: the server's response is authoritative. `applyError` always
 * replaces fieldErrors/formError wholesale rather than merging into the
 * previous attempt's, so a resubmission never shows a stale field error for
 * something the latest response says is fine. Native HTML validation
 * (`required`, `min`, ...) still gives immediate feedback and blocks a submit
 * before it reaches the server, but it can only check what the browser can;
 * it never suppresses or overrides a server error.
 */
export interface FieldErrors {
  [field: string]: string[]
}

export function useFormErrors() {
  const fieldErrors = ref<FieldErrors>({})
  const formError = ref<string | null>(null)

  function fieldError(field: string): string | null {
    return fieldErrors.value[field]?.[0] ?? null
  }

  /** Messages for fields the form has no dedicated slot for, so nothing the backend reports is silently dropped. */
  function otherFieldErrors(knownFields: string[]): string[] {
    return Object.entries(fieldErrors.value)
      .filter(([field]) => !knownFields.includes(field))
      .flatMap(([, messages]) => messages)
  }

  function clear(): void {
    fieldErrors.value = {}
    formError.value = null
  }

  function applyError(error: unknown, describeError: (code: string) => string): void {
    const body = apiErrorBody(error)
    fieldErrors.value = body.error === 'VALIDATION_FAILED' && body.fields ? body.fields : {}
    formError.value = describeError(body.error)
  }

  return { fieldErrors, formError, fieldError, otherFieldErrors, clear, applyError }
}
