<?php

namespace Javascript;

abstract class EnvironmentRecord {
  /**
   * Determine if an Environment Record has a binding for the String value N. Return true if it does and false if it does not.
   *
   * @param  string  $name
   *
   * @return boolean
   */
  abstract function HasBinding(string $name): bool;

  /**
   * Create a new but uninitialized mutable binding in an Environment Record. The String value N is the text of the bound name. If the Boolean argument D is true the binding may be subsequently deleted.
   *
   * @param  string  $name
   * @param  boolean $deletable
   *
   * @return void
   */
  abstract function CreateMutableBinding(string $name, bool $deletable): void;

  /**
   * Create a new but uninitialized immutable binding in an Environment Record. The String value N is the text of the bound name. If S is true then attempts to set it after it has been initialized will always throw an exception, regardless of the strict mode setting of operations that reference that binding.
   *
   * @param  string  $name
   * @param  boolean $strict
   *
   * @return void
   */
  abstract function CreateImmutableBinding(string $name, bool $strict): void;
  /**
   * Set the value of an already existing but uninitialized binding in an Environment Record. The String value N is the text of the bound name. V is the value for the binding and is a value of any ECMAScript language type.
   *
   * TODO: Undefined, Null, Boolean, String, Symbol, Number, BigInt, and Object
   *
   * @param  string                 $name
   * @param  ECMAScriptLanguageType $value
   *
   * @return void
   */
  abstract function InitializeBinding(string $name, ECMAScriptLanguageType $value): void;

  /**
   * Set the value of an already existing mutable binding in an Environment Record. The String value N is the text of the bound name. V is the value for the binding and may be a value of any ECMAScript language type. S is a Boolean flag. If S is true and the binding cannot be set throw a TypeError exception.
   *
   * @param  string                 $name
   * @param  ECMAScriptLanguageType $value
   * @param  boolean                $strict
   *
   * @return void
   */
  abstract function SetMutableBinding(string $name, ECMAScriptLanguageType $value, bool $strict): void;

  /**
   * Returns the value of an already existing binding from an Environment Record. The String value N is the text of the bound name. S is used to identify references originating in strict mode code or that otherwise require strict mode reference semantics. If S is true and the binding does not exist throw a ReferenceError exception. If the binding exists but is uninitialized a ReferenceError is thrown, regardless of the value of S.
   *
   * @param  string  $name
   * @param  boolean $strict
   *
   * @return void
   */
  abstract function GetBindingValue(string $name, bool $strict): void;

  /**
   * Delete a binding from an Environment Record. The String value N is the text of the bound name. If a binding for N exists, remove the binding and return true. If the binding exists but cannot be removed return false. If the binding does not exist return true.
   *
   * @param  string  $name
   *
   * @return boolean
   */
  abstract function DeleteBinding(string $name): bool;

  /**
   * Determine if an Environment Record establishes a this binding. Return true if it does and false if it does not.
   *
   * @return boolean
   */
  abstract function HasThisBinding(): bool;

  /**
   * Determine if an Environment Record establishes a super method binding. Return true if it does and false if it does not.
   *
   * @return boolean
   */
  abstract function HasSuperBinding(): bool;

  /**
   * If this Environment Record is associated with a with statement, return the with object. Otherwise, return undefined.
   *
   * @return ECMAScriptLanguageType
   */
  abstract function WithBaseObject(): ECMAScriptLanguageType;
}
