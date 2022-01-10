
## MhsDesign.FusionAfxInEel
### Super extreme experimental!

```
root = afx`
  <p>
      {Array.join(Array.map([1, 2, 3], item => afx(
          <a>{item}</a>
      ).withContext({item: item})), '')}
  </p>
`


<a href={afx(<Neos.Fusion:UriBuilder action="someMethod" />)}>Click me</a>

{something ? <Button>dasdas</Button> : <Button2/>}
```

tries to solve:

https://github.com/neos/fusion-afx/issues/28

and on secondary basis:

https://github.com/neos/fusion-afx/issues/7
