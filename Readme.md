## MhsDesign.FusionAfxInEel
### Experimental package. Not for Production.

solves: https://github.com/neos/fusion-afx/issues/28

and on secondary basis: https://github.com/neos/fusion-afx/issues/7

If you want more info, look at some tests: https://github.com/mhsdesign/MhsDesign.FusionAfxInEel/tree/main/Tests/Functional

## Examples:

(I used made up helpers like `Array.loop()` and `Function.call()` in the examples.)

AFX:
```
<a href={afx(<Neos.Fusion:UriBuilder action="someMethod" />)}>Click me</a>
```

```
{something ? afx(<Button>dasdas</Button>) : afx(<Button2/>)}
```
Fusion:
```
root = afx`
  Hello <del>JSX</del> AFX!
  {something
    ? afx(<Button>true</Button>)
    : afx(<Button2/>)}
`
```
is an equivalent to:
```
root = Neos.Fusion:Join {
    // Hidden Fusion for: Hello <del>JSX</del> AFX!
    item_4.@afxContent.0 = afx`<Button>true</Button>`
    item_4.@afxContent.1 = afx`<Button2/>`
    item_4 = ${something
        ? Mhs.AfxContent.new(mhsRuntimePath, 0, false)
        : Mhs.AfxContent.new(mhsRuntimePath, 1, false)}
}
```
(If you're unsure how AFX is transpiled generally, visit: https://afx-converter.marchenry.de/)


## AFX in Closure
```
root = Neos.Fusion:Component {

    greet = ${name => afx(
        <h1>Hello {name}</h1>
    )}

    renderer = afx`
        <div>
            {Function.call(props.greet, 'Marc Henry')}
        </div>
    `
}
```

```
root = afx`
  <p>
      {Array.loop([1, 2, 3], item => afx(
          <a>{item}</a>
      ))}
  </p>
`
```

## Example of all craziness combined:
```
root = ${
  Array.loop([1, 2], val => afx(
    <Neos.Fusion:Tag tagName="h3">
      Actual AFX!
    </Neos.Fusion:Tag>
    
    <p>
      {val % 2 ? afx(Even nested) : afx(<Neos.Fusion:Value value={'cool ' + val} />) }
    </p>

    <b>Current:</b> {val}
  ))
}
```

### Passing context:
ways to pass context vars to `afx()` are:
- All outer context vars are already available (except `this`)
- add chainable method `afx(...).use(context)`. Where `context` is an eel object (associative  array) like `afx(<p>{foo}</p>).use({foo: 'someValue'})`.
This also works for nested objects.
- is afx **Directly** preceded by a closure arrow syntax like: `(foo, bar) => afx(...)` the arguments `foo` and `bar` will be passed to the afx on render.

### Details:
- syntax could also be instead of `afx()` `${eel + (<>for strings and eel only in 'fragment'</>) + (<p></p>)}`. Determined by `(<` and `>)`
- implementing a way to write AFX inside eel would be much harder, as there is no way to extract with regex AFX/XML/HTML syntax from a string.
- to use `this` in `afx()` pass paths with `.use(path: this.path)`

### Internal:
Since `afx()` is a parsing time language construct, the parser can tell, if a string should be directly returned or when a valid chainable like `use` or `withContext` is preset which will lead to the return of `$this` and a delayed evaluation.

#### Eel `mhsRuntimePath` context
Each eel expression has similar to `this` via `mhsRuntimePath` access to the current runtime, its being used and the current path of the eel expression in the runtime.

Since AOP doesn't support recursion, the runtime is extended (similar to https://github.com/mhsdesign/MhsDesign.FusionTypeHints, as why does packages won't work together without adaptation)

There is also an approach without `mhsRuntimePath` and using `this`.
It's way too unreliable. But if you're interested, checkout the branch `usingThis`.

...

If you want more info, look at some tests: https://github.com/mhsdesign/MhsDesign.FusionAfxInEel/tree/main/Tests/Functional
