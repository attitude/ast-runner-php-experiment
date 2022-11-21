// TODO: Remove void
export type Primitive = string | number | boolean | number | undefined | void
export type NestedCodeLines = (Primitive | NestedCodeLines)[]
export type Transpiled = Primitive | NestedCodeLines
