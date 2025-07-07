# ServiceManager

As of laminas-db 3.0.0 an AdapterManager has been introduced to manage the
adapter dependencies and to provide a means to allow the platform packages
to register their own factories for the required dependencies. This is
handled by the PhpDb\Adapter\Mysql\Container\AdapterManagerDelegator class.
