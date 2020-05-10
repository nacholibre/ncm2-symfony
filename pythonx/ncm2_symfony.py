# -*- coding: utf-8 -*-

import vim
from ncm2 import Ncm2Source, getLogger, Popen
import re
from copy import deepcopy
import subprocess
import json
import os

logger = getLogger(__name__)


class Source(Ncm2Source):

    def __init__(self, nvim):
        super(Source, self).__init__(nvim)
        self.completion_timeout = self.nvim.eval('g:ncm2_phpactor_timeout') or 5

    def on_complete(self, ctx, extension, lines):
        if not extension:
            extension = '0'

        matches = []

        src = "\n".join(lines)
        src = self.get_src(src, ctx)

        lnum = ctx['lnum']

        # use byte addressing
        bcol = ctx['bcol']
        src = src.encode()

        pos = self.lccol2pos(lnum, bcol, src)

        # get the php autocomplete bin path
        dir_path = os.path.dirname(os.path.realpath(__file__))
        autocomplete_bin_path = dir_path + '/../bin/app.php'

        args = ['php', autocomplete_bin_path, 'complete', '-d', os.getcwd(), '-p', str(pos), '-e', extension]
        #args = ['php', autocomplete_bin_path, '-d', os.getcwd(), str(pos)]
        proc = Popen(args=args,
                     stdin=subprocess.PIPE,
                     stdout=subprocess.PIPE,
                     stderr=subprocess.DEVNULL)

        result, errs = proc.communicate(src, timeout=self.completion_timeout)

        result = result.decode()

        logger.debug("extension: %s", extension)

        logger.debug("args: %s", args)
        #logger.debug("result: [%s]", result)

        #logger.debug(result)
        result = json.loads(result)

        if not result or not result.get('suggestions', None):
            logger.debug('No matches found...abort')
            return

        matches = []
        for e in result['suggestions']:
            shortDescription = e['short_description']
            word = e['name']
            #t = e['type']

            #item = {'word': word, 'menu': menu, 'info': menu}
            item = dict(word=word, menu=shortDescription, info=shortDescription)

            #snip_args = 'args'
            #ph0 = '${%s:%s}' % ('num', 'txt')

            #snippet = '%s(%s)%s' % (word, snip_args, ph0)

            #item['user_data'] = {'snippet': snippet, 'is_snippet': 1}
            matches.append(item)

        self.complete(ctx, ctx['startccol'], matches)


source = Source(vim)

on_complete = source.on_complete
