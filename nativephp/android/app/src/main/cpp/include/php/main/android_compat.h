#ifndef ANDROID_COMPAT_H
#define ANDROID_COMPAT_H
#ifdef __ANDROID__

#include <glob.h>

int getdtablesize(void);

/* Provide glob/globfree declarations for API < 28 */
#if __ANDROID_API__ < 28
int glob(const char *pattern, int flags,
         int (*errfunc)(const char *epath, int eerrno),
         glob_t *pglob);
void globfree(glob_t *pglob);
#endif

#endif
#endif
