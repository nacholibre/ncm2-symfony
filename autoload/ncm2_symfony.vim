if get(s:, 'loaded', 0)
    finish
endif
let s:loaded = 1

let g:ncm2_symfony#proc = yarp#py3({
    \ 'module': 'ncm2_symfony',
    \ 'on_load': { -> ncm2#set_ready(g:ncm2_symfony#source)}
    \ })

let g:ncm2_symfony#source = extend(get(g:, 'ncm2_symfony#source', {}), {
            \ 'name': 'symfony',
            \ 'ready': 0,
            \ 'priority': 8,
            \ 'mark': 'b',
            \ 'on_complete': 'ncm2_symfony#on_complete',
            \ 'on_warmup': 'ncm2_symfony#on_warmup',
            \ }, 'keep')

func! ncm2_symfony#init()
    call ncm2#register_source(g:ncm2_symfony#source)
endfunc

func! ncm2_symfony#on_warmup(ctx)
    call g:ncm2_symfony#proc.jobstart()
endfunc

func! ncm2_symfony#on_complete(ctx)
    call g:ncm2_symfony#proc.try_notify('on_complete',
                \ a:ctx,
                \ expand('%:e'),
                \ getline(1, '$'))
endfunc
