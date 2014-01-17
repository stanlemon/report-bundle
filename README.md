LemonReportBundle
========================

This bundle is a work in progress.

It is intended to take a list of reports configured in the database and present
a standard way of rendering them.

Todos:
- Figure out a better way of abstracting the route generator, this is needed for
  pagerfanta as well as for the views themselves.
- Eliminate FrameworkExtraBundle from the Controller, right now we're just using the
  Route annotation to load the controller as a service.  If we can get the bulk of
  that logic out of the Controller then it can be ContainerAware and not need to
  be registered as a service at all.
- Write tests for all the things.
